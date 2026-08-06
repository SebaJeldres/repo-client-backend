<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VectorDatabaseController extends Controller
{
    public function index()
    {
        $aiServiceUrl = config('services.ai_service.url', env('AI_SERVICE_URL', 'http://ai_service:8000'));

        try {
            $response = Http::timeout(6)->get("{$aiServiceUrl}/api/v1/invoice/invoices");

            if ($response->successful()) {
                $vectorData = $response->json();
            } else {
                $vectorData = ['total' => 0, 'data' => []];
            }
        } catch (\Exception $e) {
            $vectorData = ['total' => 0, 'data' => [], 'error' => $e->getMessage()];
        }

        return view('vector_db.index', compact('vectorData'));
    }
}