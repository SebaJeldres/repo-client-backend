<!-- Backdrop / Fondo oscuro -->
<div id="ai-chat-backdrop" 
     onclick="toggleAiChat()" 
     class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300">
</div>

<!-- Drawer Flotante -->
<aside id="ai-chat-drawer" 
       class="fixed top-0 right-0 h-full bg-white dark:bg-gray-900 shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-l border-gray-200 dark:border-gray-800"
       style="width: 420px; min-width: 320px; max-width: 90vw;">

    <!-- BARRA PARA REGLAR EL ANCHO (RESIZE HANDLE) -->
    <div id="ai-chat-resize-handle" 
         class="absolute top-0 left-0 w-2 h-full cursor-ew-resize hover:bg-indigo-500/50 transition-colors group z-10 flex items-center justify-center">
        <div class="w-1 h-8 bg-gray-300 dark:bg-gray-600 rounded-full group-hover:bg-indigo-500"></div>
    </div>

    <!-- Header del Chat -->
    <div class="p-4 bg-indigo-600 dark:bg-indigo-700 text-white flex justify-between items-center select-none">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center font-bold text-lg">
                🤖
            </div>
            <div>
                <h3 class="font-bold text-base leading-tight">Asistente de Bodega</h3>
                <p class="text-xs text-indigo-100">Consultas de Facturas e Inventario</p>
            </div>
        </div>
        <div class="flex items-center space-x-1">
            <button onclick="clearAiChat()" title="Limpiar Chat" class="p-1.5 rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
            <button onclick="toggleAiChat()" title="Cerrar" class="p-1.5 rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Cuerpo del Chat (Mensajes) -->
    <div id="ai-chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 text-sm scroll-smooth">
        <div class="flex items-start space-x-2">
            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs flex-shrink-0">
                AI
            </div>
            <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-3 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 dark:border-gray-700 max-w-[85%]">
                ¡Hola! Puedo responder preguntas sobre tus facturas, productos, precios y fechas de emisión o registro. ¿En qué te ayudo?
                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-1.5">
                    <button onclick="sendQuickPrompt('¿Cuál es el resumen de las últimas facturas?')" 
                            class="text-xs bg-indigo-50 hover:bg-indigo-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-full transition border border-indigo-100 dark:border-gray-600">
                        📊 Resumen de facturas
                    </button>
                    <button onclick="sendQuickPrompt('¿Cuál es el monto total acumulado en dinero?')" 
                            class="text-xs bg-indigo-50 hover:bg-indigo-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-full transition border border-indigo-100 dark:border-gray-600">
                        💰 Monto total
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicador de Carga -->
    <div id="ai-chat-loading" class="hidden px-4 py-2 text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-2">
        <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Consultando la base de conocimientos...</span>
    </div>

    <!-- Formulario / Input -->
    <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
        <form onsubmit="submitAiChat(event)" class="flex items-center space-x-2">
            <input type="text" 
                   id="ai-chat-input" 
                   placeholder="Ej: ¿Cuál fue el precio total de la última factura?" 
                   class="flex-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
                   autocomplete="off">
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-xl shadow-md transition flex-shrink-0 flex items-center justify-center">
                <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </form>
    </div>
</aside>

<!-- Botón Flotante para Abrir -->
<button onclick="toggleAiChat()" 
        class="fixed bottom-6 right-6 bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-full shadow-2xl z-30 transition transform hover:scale-105 flex items-center space-x-2 focus:outline-none">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
    <span class="font-medium text-sm hidden sm:inline">Asistente IA</span>
</button>