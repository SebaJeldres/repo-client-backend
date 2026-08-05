<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceVectorization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;            // Reintentos automáticos
    public $backoff = [10, 30, 60]; // Segundos de espera entre reintentos

    protected Invoice $invoice;
    protected array $items;

    /**
     * @param Invoice $invoice
     * @param array $items Array opcional con el detalle de productos procesados
     */
    public function __construct(Invoice $invoice, array $items = [])
    {
        $this->invoice = $invoice;
        $this->items = $items;
    }

    public function handle(): void
    {
        // 1. Cambiar estado a 'processing'
        $this->invoice->update([
            'vector_status' => 'processing',
            'vector_error'  => null,
        ]);

        // 2. Construir el texto estructurado para el embedding RAG
        $textPayload = "Boleta N°: " . ($this->invoice->invoice_number ?? 'S/N') . "\n";
        $textPayload .= "Proveedor: " . ($this->invoice->supplier_name ?? 'Desconocido') . "\n";
        $textPayload .= "Fecha: " . ($this->invoice->issue_date ?? 'N/A') . "\n";
        $textPayload .= "Total: $" . number_format((float) $this->invoice->total_amount, 2) . "\n";

        if (!empty($this->items)) {
            $textPayload .= "Detalle de Productos:\n";
            foreach ($this->items as $item) {
                $name = $item['name'] ?? 'Producto';
                $qty  = $item['quantity'] ?? 1;
                $code = $item['code'] ?? 'N/A';
                $textPayload .= "- Código: {$code} | {$name} | Cantidad: {$qty}\n";
            }
        }

        try {
            // 3. Petición HTTP al microservicio Python (ai_service)
            $response = Http::timeout(15)->post('http://warehouse_ai_service:8000/api/v1/invoice/vectorize', [
                'invoice_id'    => $this->invoice->id,
                'document_text' => $textPayload,
                'metadata'      => [
                    'invoice_number' => $this->invoice->invoice_number,
                    'supplier_name'  => $this->invoice->supplier_name,
                    'total_amount'   => (float) $this->invoice->total_amount,
                    'user_id'        => $this->invoice->user_id,
                    'file_path'      => $this->invoice->file_path,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->invoice->update([
                    'vector_status' => 'completed',
                    'vector_id'     => $data['vector_id'] ?? "invoice_{$this->invoice->id}",
                    'vector_error'  => null,
                ]);

                Log::info("Boleta #{$this->invoice->id} vectorizada con éxito.");
            } else {
                throw new \Exception("Error respuesta ai_service ({$response->status()}): " . $response->body());
            }

        } catch (\Throwable $e) {
            Log::error("Fallo al vectorizar boleta #{$this->invoice->id}: " . $e->getMessage());

            $this->invoice->update([
                'vector_status' => 'failed',
                'vector_error'  => $e->getMessage(),
            ]);

            // Re-lanzar para activar el mecanismo de reintentos de Laravel Queues
            throw $e;
        }
    }
}