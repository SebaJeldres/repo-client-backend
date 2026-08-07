<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <span>🤖</span> {{ __('Asistente Virtual de Auditoría e Inventario') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl sm:rounded-2xl border border-gray-100 flex flex-col h-[650px] overflow-hidden">
            
            <!-- Header del Chat -->
            <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
                    <div>
                        <h3 class="text-sm font-bold">Consultas sobre Documentos y Facturas</h3>
                        <p class="text-[11px] text-slate-400">Motor RAG: Gemini + ChromaDB</p>
                    </div>
                </div>
                <span class="text-xs bg-slate-800 text-slate-300 px-3 py-1 rounded-full font-mono border border-slate-700">
                    Shield Anti-Alucinación Activo
                </span>
            </div>

            <!-- Contenedor de Mensajes -->
            <div id="chat-messages" class="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-50/50">
                <div class="flex gap-3 max-w-2xl">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm">
                        IA
                    </div>
                    <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-slate-200 text-xs text-slate-800 space-y-2">
                        <p class="font-semibold text-slate-900">¡Hola! Soy tu asistente de inventario y auditoría.</p>
                        <p>Puedes hacerme preguntas directas sobre proveedores, productos, montos o fechas registradas en tus facturas.</p>
                        <p class="text-[11px] text-indigo-600 font-mono bg-indigo-50 p-2 rounded border border-indigo-100">
                            💡 Ejemplo: "¿Qué productos le compramos a Distribuidora de Alimentos y cuánto costaron?"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario de Entrada (Pasa variables a JS mediante data-attributes) -->
            <div class="p-4 bg-white border-t border-slate-100">
                <form id="chat-form" 
                      data-url="{{ route('invoices.chat.ask') }}" 
                      data-csrf="{{ csrf_token() }}" 
                      class="flex items-center gap-2">
                    
                    <input type="text" 
                           id="question-input" 
                           placeholder="Escribe tu consulta sobre las facturas vectorizadas..." 
                           required 
                           autocomplete="off"
                           class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition outline-none">
                    
                    <button type="submit" 
                            id="send-btn"
                            class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer border-0">
                        <span>Enviar</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @vite(['resources/js/invoice-chat.js'])
</x-app-layout>