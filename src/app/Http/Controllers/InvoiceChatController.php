<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AiVectorService;
use Illuminate\Http\Request;

class InvoiceChatController extends Controller
{
    protected AiVectorService $aiService;

    public function __construct(AiVectorService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Muestra la vista del Chat RAG.
     */
    public function index()
    {
        return view('invoices.chat');
    }

    /**
     * Procesa la consulta vía AJAX y devuelve JSON.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $result = $this->aiService->askQuestion($request->input('question'));

        return response()->json($result);
    }
}