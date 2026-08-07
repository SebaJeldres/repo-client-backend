document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chat-form');
    if (!chatForm) return;

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const input = document.getElementById('question-input');
        const question = input.value.trim();
        if (!question) return;

        const chatMessages = document.getElementById('chat-messages');
        const sendBtn = document.getElementById('send-btn');

        // Leer datos desde los data-attributes del formulario
        const endpointUrl = chatForm.dataset.url;
        const csrfToken = chatForm.dataset.csrf;

        // 1. Mostrar mensaje del usuario
        appendMessage('user', question);
        input.value = '';
        sendBtn.disabled = true;
        sendBtn.classList.add('opacity-50', 'cursor-not-allowed');

        // 2. Mostrar indicador de carga
        const loadingId = appendLoading();

        try {
            // 3. Petición AJAX al backend
            const response = await fetch(endpointUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ question: question })
            });

            const data = await response.json();
            document.getElementById(loadingId).remove();

            // 4. Mostrar respuesta de la IA
            appendMessage('ai', data.answer, data.sources);

        } catch (error) {
            if (document.getElementById(loadingId)) {
                document.getElementById(loadingId).remove();
            }
            appendMessage('ai', 'Ocurrió un error al intentar comunicar con el servidor.');
        } finally {
            sendBtn.disabled = false;
            sendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });

    function appendMessage(sender, text, sources = []) {
        const chatMessages = document.getElementById('chat-messages');
        const div = document.createElement('div');

        if (sender === 'user') {
            div.className = 'flex justify-end';
            div.innerHTML = `
                <div class="bg-indigo-600 text-white p-3.5 rounded-2xl rounded-tr-none max-w-lg text-xs shadow-sm leading-relaxed">
                    ${escapeHtml(text)}
                </div>
            `;
        } else {
            div.className = 'flex gap-3 max-w-2xl';

            let sourcesHtml = '';
            
            // 🛑 CONDICIÓN MEJORADA: Solo mostrar fuentes si la IA NO dio una respuesta negativa
            const isNotFoundResponse = text.toLowerCase().includes('no dispongo') || text.toLowerCase().includes('no se encontró');

            if (sources && sources.length > 0 && !isNotFoundResponse) {
                // Eliminar duplicados de fuentes por número de factura
                const uniqueSources = Array.from(new Set(sources.map(s => s.invoice_number)))
                    .map(num => sources.find(s => s.invoice_number === num));

                sourcesHtml = `<div class="mt-3 pt-2 border-t border-slate-100 flex flex-wrap gap-1.5 items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Documentos de origen:</span>`;
                
                uniqueSources.forEach(s => {
                    const docNum = s.invoice_number !== 'N/A' ? `#${s.invoice_number}` : 'Sin N°';
                    sourcesHtml += `<span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-mono rounded border border-slate-200">
                        📄 Factura ${docNum} (${s.supplier_name})
                    </span>`;
                });
                sourcesHtml += `</div>`;
            }

            div.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm">
                    IA
                </div>
                <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-slate-200 text-xs text-slate-800 whitespace-pre-line leading-relaxed">
                    ${escapeHtml(text)}
                    ${sourcesHtml}
                </div>
            `;
        }
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function appendLoading() {
        const chatMessages = document.getElementById('chat-messages');
        const id = 'loading-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'flex gap-3 max-w-2xl';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm">
                IA
            </div>
            <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-slate-200 text-xs text-slate-500 flex items-center gap-2">
                <span class="flex h-2 w-2 relative">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                <span class="font-medium">Analizando vectores en ChromaDB y consultando con Gemini...</span>
            </div>
        `;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return id;
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});