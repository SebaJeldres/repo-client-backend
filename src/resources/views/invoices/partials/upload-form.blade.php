<div class="p-6 bg-white shadow-sm rounded-xl border border-gray-100">
    <form action="{{ route('invoices.process') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                Seleccionar documento (PDF o Imagen)
            </label>
            <input type="file" 
                   name="invoice_file" 
                   accept=".pdf,.jpeg,.jpg,.png,.webp"
                   required 
                   class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 border border-gray-200 rounded-lg p-1 cursor-pointer">
        </div>

        <div class="flex justify-end">
            <button type="submit" 
                    style="background-color: #2563eb; color: #ffffff; padding: 10px 20px; border-radius: 8px;"
                    class="inline-flex items-center gap-2 font-bold text-xs shadow-sm hover:opacity-90 active:scale-95 transition cursor-pointer border-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Escanear y Validar Boleta</span>
            </button>
        </div>
    </form>
</div>