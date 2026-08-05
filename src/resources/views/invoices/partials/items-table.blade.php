<div class="p-6 bg-white shadow-lg rounded-xl border border-gray-100 space-y-6">
    <div class="flex items-center justify-between border-b pb-4 border-gray-100">
        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <span>📋</span> Resumen de Productos Procesados
        </h3>
        <span class="text-xs {{ $rejected ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }} font-bold px-3 py-1 rounded-full">
            {{ count($items) }} Ítems Detectados
        </span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Cantidad</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Stock Actual</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Stock Resultante</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach ($items as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-gray-500 font-mono">{{ $item['code'] ?? 'N/A' }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $item['name'] ?? 'Producto' }}</td>
                        <td class="px-4 py-3 text-center font-bold text-red-600">-{{ $item['quantity'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $item['current_stock'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-center font-bold {{ isset($item['status']) && $item['status'] === 'rejected' ? 'text-gray-400' : 'text-emerald-600' }}">
                            {{ $item['final_stock'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if (isset($item['status']) && $item['status'] === 'rejected')
                                <span class="px-2 py-1 text-[10px] font-bold bg-red-100 text-red-700 rounded-md">Rechazado</span>
                            @else
                                <span class="px-2 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 rounded-md">Aprobado</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- BOTÓN PRINCIPAL DE ACCIÓN -->
    <div class="flex justify-end pt-4 border-t border-gray-100">
        @if (!$rejected)
            <button type="button" 
                    onclick="document.getElementById('confirmModal').classList.remove('hidden')" 
                    style="background-color: #059669; color: #ffffff; padding: 12px 24px; border-radius: 8px; line-height: 1.5;"
                    class="inline-flex items-center gap-2 font-bold text-xs shadow-md hover:opacity-90 active:scale-95 transition cursor-pointer border-0">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Confirmar y Procesar </span>
            </button>
        @else
            <button type="button" 
                    disabled
                    style="background-color: #9ca3af; color: #ffffff; padding: 12px 24px; border-radius: 8px;"
                    class="inline-flex items-center gap-2 font-bold text-xs cursor-not-allowed border-0 opacity-70">
                <span>🚫 Proceso Bloqueado (Corrija los errores)</span>
            </button>
        @endif
    </div>
</div>