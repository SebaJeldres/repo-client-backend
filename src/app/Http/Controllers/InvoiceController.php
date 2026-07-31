<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InvoiceController extends Controller
{
    /**
     * Muestra el formulario para subir la factura.
     */
    public function index()
    {
        return view('invoices.upload');
    }

    /**
     * Procesa la factura enviándola al microservicio de IA.
     */
    public function process(Request $request)
    {
        // 1. Validar que se haya subido un archivo permitido
        $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,jpeg,png,webp|max:10240', // máx 10MB
        ]);

        $file = $request->file('invoice_file');

        try {
            // 2. Consumir el microservicio Python por la red interna de Docker
            // Usamos el nombre del servicio 'warehouse_ai_service' en lugar de localhost
            $response = Http::timeout(60) // Tiempo de espera extendido para procesamiento de IA
                ->attach(
                    'file', 
                    file_get_contents($file->getRealPath()), 
                    $file->getClientOriginalName()
                )
                ->post('http://warehouse_ai_service:8000/api/v1/invoice/process');

            // 3. Verificar si el microservicio respondió correctamente
            if ($response->successful()) {
                $extractedData = $response->json();

                return view('invoices.upload', [
                    'invoiceData' => $extractedData,
                    'success' => 'Factura procesada con éxito por la IA.'
                ]);
            }

            return back()->withErrors([
                'error' => 'El microservicio de IA devolvió un error: ' . ($response->json('detail') ?? 'Error desconocido')
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'No se pudo conectar con el microservicio de IA: ' . $e->getMessage()
            ]);
        }
    }
}