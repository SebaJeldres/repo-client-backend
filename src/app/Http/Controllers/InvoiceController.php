<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
            // 1. Obtener respuesta de FastAPI
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

            // 2. Validar cada producto extraído contra la Base de Datos
            foreach ($items as $item) {
                $code = $item['sku_or_code'] ?? null;
                $name = $item['name'] ?? '';
                $qtyRequested = (int) ($item['quantity'] ?? 1);

                // Buscar por código o por nombre exacto
                $product = Product::where(function($query) use ($code, $name) {
                    if ($code) $query->where('code', $code);
                    $query->orWhere('name', 'LIKE', '%' . $name . '%');
                })->first();

                // Regla 1: ¿Existe el producto?
                if (!$product) {
                    $errors[] = "El producto '{$name}' (Código: " . ($code ?? 'N/A') . ") no existe en el inventario.";
                    continue;
                }

                // Regla 2: ¿Hay stock suficiente para el descuento?
                if ($product->stock < $qtyRequested) {
                    $errors[] = "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$qtyRequested}.";
                    continue;
                }

                // Calcular stock resultante
                $stockAfterDiscount = $product->stock - $qtyRequested;

                // Regla 3: ¿Quedará con stock menor o igual al mínimo?
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

                $processedItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'quantity' => $qtyRequested,
                    'unit_price' => $item['unit_price'] ?? $product->price,
                    'current_stock' => $product->stock,
                    'final_stock' => $stockAfterDiscount,
                ];
            }

            // RECHAZO TOTAL si hay errores de inexistencia o stock
            if (count($errors) > 0) {
                return back()->withInput()->with('rejected_errors', $errors);
            }

            return view('invoices.upload', [
                'invoiceData' => $extractedData,
                'processedItems' => $processedItems,
                'lowStockAlerts' => $lowStockAlerts,
                'success' => 'Boleta analizada correctamente. Todos los productos existen y cuentan con stock.'
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al conectar con la IA: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesa la confirmación final descontando el stock
     */
    public function confirm(Request $request)
    {
        // Validar la contraseña del usuario actual
        $request->validate([
            'password' => ['required', 'string'],
            'items' => ['required', 'array'],
        ]);

        if (!Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'La contraseña ingresada es incorrecta. Operación cancelada.']);
        }

        // Transacción Atómica para descontar stock en MySQL
        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }
        });

        return redirect()->route('products.index')->with('success', '¡Stock descontado exitosamente de la bodega!');
    }
}