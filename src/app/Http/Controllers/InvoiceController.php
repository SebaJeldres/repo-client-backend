<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Jobs\ProcessInvoiceVectorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoices.upload');
    }

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
                        'code' => $code ?? 'N/A',
                        'name' => $name ?: 'Producto no encontrado',
                        'quantity' => $qtyRequested,
                        'current_stock' => '-',
                        'final_stock' => '-',
                        'status' => 'rejected',
                        'reason' => 'El producto no existe en el sistema.'
                    ];
                    $errors[] = "El producto '{$name}' (Código: " . ($code ?? 'N/A') . ") no existe en el inventario.";
                    continue;
                }

                $stockAfterDiscount = $product->stock - $qtyRequested;

                // Regla 2: ¿Hay stock suficiente para el descuento?
                if ($product->stock < $qtyRequested) {
                    $hasError = true;
                    $processedItems[] = [
                        'product_id' => $product->id,
                        'code' => $product->code,
                        'name' => $product->name,
                        'quantity' => $qtyRequested,
                        'current_stock' => $product->stock,
                        'final_stock' => $stockAfterDiscount,
                        'status' => 'rejected',
                        'reason' => "Stock insuficiente. Disponible: {$product->stock}, Solicitado: {$qtyRequested}"
                    ];
                    $errors[] = "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$qtyRequested}.";
                    continue;
                }

                // Regla 3: Si se aprueba, ¿Quedará con stock menor o igual al límite mínimo?
                if ($stockAfterDiscount <= $product->minimum_stock) {
                    $lowStockAlerts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'current_stock' => $product->stock,
                        'discount' => $qtyRequested,
                        'final_stock' => $stockAfterDiscount,
                        'minimum_stock' => $product->minimum_stock
                    ];
                }

                // Producto Aprobado
                $processedItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'quantity' => $qtyRequested,
                    'unit_price' => $item['unit_price'] ?? $product->price,
                    'current_stock' => $product->stock,
                    'final_stock' => $stockAfterDiscount,
                    'status' => 'approved',
                    'reason' => 'OK'
                ];
            }

            // 3. Si la boleta es válida, guardar en BD y despachar Job de vectorización
            if (!$hasError) {
                // Guardar archivo físico
                $filePath = $file->store('invoices', 'public');

                // Crear registro en BD
                $invoice = Invoice::create([
                    'user_id'        => $request->user()->id,
                    'invoice_number' => $extractedData['invoice_number'] ?? 'N/A',
                    'supplier_name'  => $extractedData['supplier_name'] ?? 'Proveedor Desconocido',
                    'total_amount'   => $extractedData['total_amount'] ?? 0,
                    'issue_date'     => $extractedData['issue_date'] ?? now()->toDateString(),
                    'file_path'      => $filePath,
                    'vector_status'  => 'pending',
                ]);

                // 💡 PASAMOS LOS ITEMS EN EL SEGUNDO PARÁMETRO
                ProcessInvoiceVectorization::dispatch($invoice, $processedItems);
            }

            // Devolver respuesta a la vista
            return view('invoices.upload', [
                'invoiceData'    => $extractedData,
                'processedItems' => $processedItems,
                'lowStockAlerts' => $lowStockAlerts,
                'rejectedErrors' => $errors,
                'isRejected'     => $hasError,
                'success'        => $hasError ? null : 'Boleta analizada correctamente. Se ha encolado para su vectorización.'
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar la boleta: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesa la confirmación final descontando el stock
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'password'           => ['required', 'string'],
            'items'              => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        // Si la clave es incorrecta, devolvemos todo lo que venía en el formulario
        if (!Hash::check($request->password, $request->user()->password)) {
            return back()
                ->withInput()
                ->withErrors(['password' => 'La contraseña ingresada es incorrecta. Operación cancelada.']);
        }

        // Transacción Atómica de actualización en Base de Datos
        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }
        });

        return redirect()->route('products.index')->with('success', '¡Inventario actualizado correctamente!');
    }
}