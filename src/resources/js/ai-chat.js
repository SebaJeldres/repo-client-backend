/**
 * Asistente IA - Drawer / Chat JavaScript
 */

// Referencias DOM
let drawer, backdrop, chatMessages, chatInput, loadingIndicator;

const STORAGE_KEY = 'ai_chat_history';
const STATE_KEY = 'ai_chat_is_open';
const WIDTH_KEY = 'ai_chat_width';

let tableCounter = 0; // Contador global para dar un ID único a cada tabla renderizada

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
        drawer.classList.add('translate-x-full');
        backdrop?.classList.add('opacity-0');
        setTimeout(() => backdrop?.classList.add('hidden'), 300);
        sessionStorage.setItem(STATE_KEY, 'false');
    } else {
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

    appendMessage('user', query);
    chatInput.value = '';
    saveChatHistory();

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

// 3. Renderizar Mensajes
function appendMessage(sender, text, sources = []) {
    if (!chatMessages) getDomElements();
    if (!chatMessages) return;

    const messageDiv = document.createElement('div');

    if (sender === 'user') {
        messageDiv.className = 'flex justify-end';
        messageDiv.innerHTML = `
            <div class="bg-indigo-600 text-white p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[85%] text-sm leading-relaxed">
                ${escapeHtml(text)}
            </div>
        `;
    } else {
        messageDiv.className = 'flex items-start space-x-2';

        let sourcesHtml = '';
        
        const isNotFoundResponse = text.toLowerCase().includes('no dispongo') || 
                                   text.toLowerCase().includes('no se encontró') || 
                                   text.toLowerCase().includes('no se encontraron');

        if (sources && sources.length > 0 && !isNotFoundResponse) {
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
            <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-3 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 dark:border-gray-700 max-w-[85%] text-sm">
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
    if (!text) return '';

    let html = escapeHtml(text.trim());

    // 1. Convertir Tablas Markdown
    const tableRegex = /\|(.+)\|\r?\n\|[ -|:-]+\|\r?\n((\|.+\|\r?\n?)+)/g;

    html = html.replace(tableRegex, (match) => {
        const lines = match.trim().split(/\r?\n/).filter(line => line.trim() !== '');

        if (lines.length < 2) return match;

        tableCounter++;
        const tableId = `ai-table-${tableCounter}`;

        const headers = lines[0].split('|').filter(cell => cell.trim() !== '');
        const rows = lines.slice(2).map(line => line.split('|').filter(cell => cell.trim() !== ''));

        let tableHtml = '<div class="overflow-x-auto my-2 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">' +
            /* Contenedor superior solo con los nuevos botones */
            '<div class="p-2 border-b border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-1.5 select-none bg-gray-50/50 dark:bg-gray-800/50">' +
                `<button type="button" onclick="exportTableToExcel('${tableId}')" title="Exportar a Excel" class="px-2 py-1 inline-flex items-center space-x-1 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded-md text-[11px] font-medium transition duration-150 active:scale-95">` +
                    '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' +
                    '<span>Excel</span>' +
                '</button>' +
                `<button type="button" onclick="exportTableToPDF('${tableId}')" title="Exportar a PDF" class="px-2 py-1 inline-flex items-center space-x-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 rounded-md text-[11px] font-medium transition duration-150 active:scale-95">` +
                    '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' +
                    '<span>PDF</span>' +
                '</button>' +
            '</div>' +
            /* Estructura original de la tabla */
            `<table id="${tableId}" class="w-full text-left text-xs border-collapse" style="margin: 0 !important; border-spacing: 0;">` +
                '<thead class="bg-indigo-50 dark:bg-gray-800 text-indigo-900 dark:text-indigo-200 font-semibold">' +
                    '<tr>' +
                        headers.map(h => `<th class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">${h.trim()}</th>`).join('') +
                    '</tr>' +
                '</thead>' +
                '<tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">';

        rows.forEach(row => {
            if (row.length > 0) {
                tableHtml += '<tr>';
                row.forEach(cell => {
                    tableHtml += `<td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">${cell.trim()}</td>`;
                });
                tableHtml += '</tr>';
            }
        });

        tableHtml += '</tbody></table></div>';

        return `___TABLE_START___${tableHtml}___TABLE_END___`;
    });

    // 2. Encabezados, código, negritas e itálicas
    html = html.replace(/^### (.*$)/gim, '<h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-2 mb-1">$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2 class="text-base font-bold text-indigo-700 dark:text-indigo-300 mt-2 mb-1">$1</h2>');
    html = html.replace(/^---$/gim, '<hr class="my-2 border-gray-200 dark:border-gray-700">');
    html = html.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 rounded text-xs font-mono">$1</code>');
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // 3. Separar por bloques para NO meter <br> dentro de la tabla
    const parts = html.split(/(___TABLE_START___[\s\S]*?___TABLE_END___)/g);

    html = parts.map(part => {
        if (part.startsWith('___TABLE_START___')) {
            return part.replace('___TABLE_START___', '').replace('___TABLE_END___', '');
        } else {
            return part.replace(/\r?\n/g, '<br>');
        }
    }).join('');

    // 4. Limpieza de <br> innecesarios alrededor del contenedor
    html = html.replace(/(<br\s*\/?>\s*)+(<div class="overflow-x-auto)/g, '$2');
    html = html.replace(/(<\/div>)\s*(<br\s*\/?>)+/g, '$1');

    return html.trim();
}

// -------------------------------------------------------------
// FUNCIONES DE EXPORTACIÓN (EXCEL Y PDF)
// -------------------------------------------------------------

function exportTableToExcel(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    if (typeof XLSX === 'undefined') {
        alert('Cargando librería de Excel, intenta de nuevo en unos segundos...');
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: "Resumen" });
    XLSX.writeFile(workbook, `reporte_facturas_${new Date().toISOString().slice(0,10)}.xlsx`);
}

function exportTableToPDF(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    if (!window.jspdf || !window.jspdf.jsPDF) {
        alert('Cargando librería de PDF, intenta de nuevo en unos segundos...');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');

    doc.setFontSize(14);
    doc.setTextColor(40);
    doc.text("Resumen de Datos - Asistente de Bodega", 40, 40);

    doc.autoTable({
        html: `#${tableId}`,
        startY: 60,
        styles: { fontSize: 9, cellPadding: 6 },
        headStyles: { fillColor: [79, 70, 229], textColor: [255, 255, 255], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [249, 250, 251] },
        margin: { top: 60, left: 40, right: 40 },
    });

    doc.save(`reporte_facturas_${new Date().toISOString().slice(0,10)}.pdf`);
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
                <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-3 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 dark:border-gray-700 max-w-[85%] text-sm">
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

function sendQuickPrompt(promptText) {
    if (!chatInput) getDomElements();
    if (chatInput) {
        chatInput.value = promptText;
        submitAiChat();
    }
}

// Exponer funciones globales para eventos inline de HTML
window.toggleAiChat = toggleAiChat;
window.submitAiChat = submitAiChat;
window.clearAiChat = clearAiChat;
window.sendQuickPrompt = sendQuickPrompt;
window.exportTableToExcel = exportTableToExcel;
window.exportTableToPDF = exportTableToPDF;