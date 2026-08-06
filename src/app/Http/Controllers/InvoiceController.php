<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Jobs\ProcessInvoiceVectorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoices.upload');
    }

    /**
     * Procesa la imagen/PDF con la IA y valida contra la base de datos (Pre-visualización)
     */
    public function process(Request $request)
    {
        $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,jpeg,png,webp|max:10240',
        ]);

        $file = $request->file('invoice_file');

        try {
            // 1. Obtener respuesta del microservicio de FastAPI
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post('http://warehouse_ai_service:8000/api/v1/invoice/process');

            if (!$response->successful()) {
                return back()->withErrors([
                    'error' => 'El microservicio de IA devolvió un error: ' . ($response->json('detail') ?? 'Error desconocido')
                ]);
            }

            $extractedData = $response->json();
            $items = $extractedData['items'] ?? [];

            $processedItems = [];
            $errors = [];
            $lowStockAlerts = [];
            $hasError = false;

            // 2. Validar cada producto extraído contra la Base de Datos
            foreach ($items as $item) {
                $code = $item['sku_or_code'] ?? $item['code'] ?? null;
                $name = $item['name'] ?? '';
                $qtyRequested = (int) ($item['quantity'] ?? 1);

                // Buscar por código o por coincidencia parcial de nombre
                $product = Product::where(function ($query) use ($code, $name) {
                    if ($code) {
                        $query->where('code', $code);
                    }
                    if ($name) {
                        $query->orWhere('name', 'LIKE', '%' . $name . '%');
                    }
                })->first();

                // Regla 1: ¿Existe el producto?
                if (!$product) {
                    $hasError = true;
                    $processedItems[] = [
                        'code'          => $code ?? 'N/A',
                        'name'          => $name ?: 'Producto no encontrado',
                        'quantity'      => $qtyRequested,
                        'current_stock' => '-',
                        'final_stock'   => '-',
                        'status'        => 'rejected',
                        'reason'        => 'El producto no existe en el sistema.'
                    ];
                    $errors[] = "El producto '{$name}' (Código: " . ($code ?? 'N/A') . ") no existe en el inventario.";
                    continue;
                }

                $stockAfterDiscount = $product->stock - $qtyRequested;

                // Regla 2: ¿Hay stock suficiente para el descuento?
                if ($product->stock < $qtyRequested) {
                    $hasError = true;
                    $processedItems[] = [
                        'product_id'    => $product->id,
                        'code'          => $product->code,
                        'name'          => $product->name,
                        'quantity'      => $qtyRequested,
                        'current_stock' => $product->stock,
                        'final_stock'   => $stockAfterDiscount,
                        'status'        => 'rejected',
                        'reason'        => "Stock insuficiente. Disponible: {$product->stock}, Solicitado: {$qtyRequested}"
                    ];
                    $errors[] = "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$qtyRequested}.";
                    continue;
                }

                // Regla 3: Si se aprueba, ¿Quedará con stock menor o igual al límite mínimo?
                if ($stockAfterDiscount <= $product->minimum_stock) {
                    $lowStockAlerts[] = [
                        'id'            => $product->id,
                        'name'          => $product->name,
                        'code'          => $product->code,
                        'current_stock' => $product->stock,
                        'discount'      => $qtyRequested,
                        'final_stock'   => $stockAfterDiscount,
                        'minimum_stock' => $product->minimum_stock
                    ];
                }

                // Producto Aprobado
                $processedItems[] = [
                    'product_id'    => $product->id,
                    'name'          => $product->name,
                    'code'          => $product->code,
                    'quantity'      => $qtyRequested,
                    'unit_price'    => $item['unit_price'] ?? $product->price,
                    'current_stock' => $product->stock,
                    'final_stock'   => $stockAfterDiscount,
                    'status'        => 'approved',
                    'reason'        => 'OK'
                ];
            }

            // Guardar archivo temporalmente para moverlo en la confirmación
            $tempFilePath = $file->store('temp_invoices', 'public');

            // Devolver respuesta a la vista previa para confirmación del usuario
            return view('invoices.upload', [
                'invoiceData'    => $extractedData,
                'processedItems' => $processedItems,
                'lowStockAlerts' => $lowStockAlerts,
                'rejectedErrors' => $errors,
                'isRejected'     => $hasError,
                'tempFilePath'   => $tempFilePath,
                'success'        => $hasError ? null : 'Boleta analizada correctamente. Revisa la información y confirma la operación.'
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar la boleta: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesa la confirmación final: descuenta stock, guarda la factura y gatilla la vectorización
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'password'           => ['required', 'string'],
            'items'              => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'invoice_number'     => ['nullable', 'string'],
            'supplier_name'      => ['nullable', 'string'],
            'total_amount'       => ['nullable', 'numeric'],
            'issue_date'         => ['nullable', 'date'],
            'temp_file_path'     => ['nullable', 'string'],
        ]);

        // Verificar contraseña del usuario
        if (!Hash::check($request->password, $request->user()->password)) {
            return back()
                ->withInput()
                ->withErrors(['password' => 'La contraseña ingresada es incorrecta. Operación cancelada.']);
        }

        try {
            $invoice = null;

            // Transacción atómica en BD MySQL
            DB::transaction(function () use ($request, &$invoice) {
                // 1. Mover archivo temporal a carpeta definitiva
                $finalFilePath = $request->temp_file_path;
                if ($request->temp_file_path && Storage::disk('public')->exists($request->temp_file_path)) {
                    $fileName = basename($request->temp_file_path);
                    $finalFilePath = 'invoices/' . $fileName;
                    Storage::disk('public')->move($request->temp_file_path, $finalFilePath);
                }

                // 2. Crear registro oficial de la Factura en BD
                $invoice = Invoice::create([
                    'user_id'        => $request->user()->id,
                    'invoice_number' => $request->input('invoice_number', 'N/A'),
                    'supplier_name'  => $request->input('supplier_name', 'Proveedor Desconocido'),
                    'total_amount'   => $request->input('total_amount', 0),
                    'issue_date'     => $request->input('issue_date', now()->toDateString()),
                    'file_path'      => $finalFilePath ?? 'invoices/default.pdf',
                    'vector_status'  => 'pending',
                ]);

                // 3. Descontar el stock de los productos
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $product->decrement('stock', $item['quantity']);
                }
            });

            // 4. DESPACHAR JOB DE VECTORIZACIÓN SOLO TRAS CONFIRMAR EXITOSAMENTE
            if ($invoice) {
                ProcessInvoiceVectorization::dispatch($invoice, $request->items);
            }

            return redirect()->route('products.index')
                ->with('success', '¡Inventario actualizado y boleta enviada a vectorizar con éxito!');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al confirmar la boleta: ' . $e->getMessage()]);
        }
    }
}