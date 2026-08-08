/**
 * Asistente IA - Drawer / Chat JavaScript
 */

// Referencias DOM
let drawer, backdrop, chatMessages, chatInput, loadingIndicator;

const STORAGE_KEY = 'ai_chat_history';
const STATE_KEY = 'ai_chat_is_open';
const WIDTH_KEY = 'ai_chat_width';

function getDomElements() {
    drawer = document.getElementById('ai-chat-drawer');
    backdrop = document.getElementById('ai-chat-backdrop');
    chatMessages = document.getElementById('ai-chat-messages');
    chatInput = document.getElementById('ai-chat-input');
    loadingIndicator = document.getElementById('ai-chat-loading');
}

// 1. Abrir / Cerrar Drawer
function toggleAiChat() {
    if (!drawer) getDomElements();
    if (!drawer) return;

    const isOpen = !drawer.classList.contains('translate-x-full');

    if (isOpen) {
        // Cerrar
        drawer.classList.add('translate-x-full');
        backdrop?.classList.add('opacity-0');
        setTimeout(() => backdrop?.classList.add('hidden'), 300);
        sessionStorage.setItem(STATE_KEY, 'false');
    } else {
        // Abrir
        backdrop?.classList.remove('hidden');
        setTimeout(() => backdrop?.classList.remove('opacity-0'), 10);
        drawer.classList.remove('translate-x-full');
        sessionStorage.setItem(STATE_KEY, 'true');
        chatInput?.focus();
        scrollToBottom();
    }
}

// 2. Enviar Mensaje
async function submitAiChat(e) {
    if (e) e.preventDefault();
    if (!chatInput) getDomElements();

    const query = chatInput.value.trim();
    if (!query) return;

    // Renderizar mensaje del usuario
    appendMessage('user', query);
    chatInput.value = '';
    saveChatHistory();

    // Mostrar spinner
    if (loadingIndicator) loadingIndicator.classList.remove('hidden');
    scrollToBottom();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/invoices/chat/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ 
                query: query,
                question: query 
            })
        });

        const data = await response.json();

        if (loadingIndicator) loadingIndicator.classList.add('hidden');

        if (response.ok && (data.answer || data.response)) {
            const answerText = data.answer || data.response;
            appendMessage('assistant', answerText, data.sources || []);
        } else {
            const errorMsg = data.message || 'Lo siento, ocurrió un error al procesar tu consulta.';
            appendMessage('assistant', errorMsg);
        }
    } catch (error) {
        console.error(error);
        if (loadingIndicator) loadingIndicator.classList.add('hidden');
        appendMessage('assistant', 'Error de conexión con el servidor.');
    }

    saveChatHistory();
    scrollToBottom();
}

// 3. Renderizar Mensajes con Filtro de Fuentes Únicas y Detección de Negativas
function appendMessage(sender, text, sources = []) {
    if (!chatMessages) getDomElements();
    if (!chatMessages) return;

    const messageDiv = document.createElement('div');

    if (sender === 'user') {
        messageDiv.className = 'flex justify-end';
        messageDiv.innerHTML = `
            <div class="bg-indigo-600 text-white p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[85%] leading-relaxed">
                ${escapeHtml(text)}
            </div>
        `;
    } else {
        messageDiv.className = 'flex items-start space-x-2';

        let sourcesHtml = '';
        
        // Detección de respuestas donde no se halló información
        const isNotFoundResponse = text.toLowerCase().includes('no dispongo') || 
                                   text.toLowerCase().includes('no se encontró') || 
                                   text.toLowerCase().includes('no se encontraron');

        if (sources && sources.length > 0 && !isNotFoundResponse) {
            // Eliminar duplicados de fuentes por número de factura
            const uniqueSources = Array.from(new Set(sources.map(s => s.invoice_number)))
                .map(num => sources.find(s => s.invoice_number === num));

            sourcesHtml = `
                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-1.5 items-center">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider w-full mb-0.5">Documentos de origen:</span>
                    ${uniqueSources.map(s => {
                        const docNum = (s.invoice_number && s.invoice_number !== 'N/A') ? `#${s.invoice_number}` : 'Sin N°';
                        const supplier = s.supplier_name || 'Proveedor';
                        return `<span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px] font-mono rounded border border-gray-200 dark:border-gray-600">
                            📄 Factura ${docNum} (${escapeHtml(supplier)})
                        </span>`;
                    }).join('')}
                </div>
            `;
        }

        messageDiv.innerHTML = `
            <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 flex items-center justify-center font-bold text-xs flex-shrink-0">
                AI
            </div>
            <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-3 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 dark:border-gray-700 max-w-[85%] leading-relaxed">
                ${formatMarkdown(text)}
                ${sourcesHtml}
            </div>
        `;
    }

    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Utilidades
function scrollToBottom() {
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatMarkdown(text) {
    return escapeHtml(text)
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
}

function saveChatHistory() {
    if (chatMessages) {
        sessionStorage.setItem(STORAGE_KEY, chatMessages.innerHTML);
    }
}

function loadChatHistory() {
    getDomElements();

    const savedHistory = sessionStorage.getItem(STORAGE_KEY);
    if (savedHistory && chatMessages) {
        chatMessages.innerHTML = savedHistory;
    }

    const wasOpen = sessionStorage.getItem(STATE_KEY) === 'true';
    if (wasOpen && drawer) {
        backdrop?.classList.remove('hidden');
        setTimeout(() => backdrop?.classList.remove('opacity-0'), 10);
        drawer.classList.remove('translate-x-full');
    }
}

function clearAiChat() {
    sessionStorage.removeItem(STORAGE_KEY);
    if (!chatMessages) getDomElements();
    if (chatMessages) {
        chatMessages.innerHTML = `
            <div class="flex items-start space-x-2">
                <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 flex items-center justify-center font-bold text-xs flex-shrink-0">
                    AI
                </div>
                <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-3 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 dark:border-gray-700 max-w-[85%]">
                    ¡Conversación reiniciada! ¿En qué te puedo ayudar?
                </div>
            </div>
        `;
    }
}

// Lógica para Redimensionar Ancho (Resize Handle)
function initResizeHandle() {
    const handle = document.getElementById('ai-chat-resize-handle');
    const drawerEl = document.getElementById('ai-chat-drawer');
    if (!handle || !drawerEl) return;

    const savedWidth = localStorage.getItem(WIDTH_KEY);
    if (savedWidth) {
        drawerEl.style.width = `${savedWidth}px`;
    }

    let isResizing = false;
    let startX = 0;
    let startWidth = 0;

    handle.addEventListener('mousedown', (e) => {
        isResizing = true;
        startX = e.clientX;
        startWidth = parseInt(document.defaultView.getComputedStyle(drawerEl).width, 10);
        drawerEl.classList.remove('transition-transform');
        document.body.style.cursor = 'ew-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;
        const dx = startX - e.clientX;
        let newWidth = startWidth + dx;
        const maxWidth = window.innerWidth * 0.9;
        if (newWidth < 320) newWidth = 320;
        if (newWidth > maxWidth) newWidth = maxWidth;
        drawerEl.style.width = `${newWidth}px`;
    });

    document.addEventListener('mouseup', () => {
        if (isResizing) {
            isResizing = false;
            drawerEl.classList.add('transition-transform');
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            const currentWidth = parseInt(drawerEl.style.width, 10);
            if (currentWidth) {
                localStorage.setItem(WIDTH_KEY, currentWidth);
            }
        }
    });
}

// Cargar estado inicial e inicializar el resize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadChatHistory();
        initResizeHandle();
    });
} else {
    loadChatHistory();
    initResizeHandle();
}

// Exponer funciones globales para eventos inline de HTML
window.toggleAiChat = toggleAiChat;
window.submitAiChat = submitAiChat;
window.clearAiChat = clearAiChat;