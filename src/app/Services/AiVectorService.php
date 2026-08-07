<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiVectorService
{
    protected string $baseUrl;

    public function __construct()
    {
        // Usa exactamente la misma configuración que tienes en VectorDatabaseController
        $this->baseUrl = config('services.ai_service.url', env('AI_SERVICE_URL', 'http://ai_service:8000'));
    }

    /**
     * Envía una consulta RAG a la base de datos vectorial vía el microservicio de IA.
     */
    public function askQuestion(string $question): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/api/v1/invoice/rag/query", [
                'question' => $question,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error en servicio RAG de IA: ' . $response->body());

            return [
                'answer' => 'Ocurrió un error al procesar tu consulta con la IA.',
                'sources' => [],
                'found_context' => false,
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al conectar con el microservicio de IA: ' . $e->getMessage());

            return [
                'answer' => 'No se pudo establecer conexión con el asistente de IA.',
                'sources' => [],
                'found_context' => false,
            ];
        }
    }
}