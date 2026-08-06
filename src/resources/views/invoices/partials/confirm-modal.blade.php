<div id="confirmModal" 
     style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"
     class="fixed inset-0 {{ $errors->has('password') ? '' : 'hidden' }} flex items-center justify-center z-50 p-4 transition-all">
    
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-gray-200 overflow-hidden">
        
        <!-- Header del Modal -->
        <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-slate-800 rounded-lg text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Autorización de Seguridad</h3>
                    <p class="text-xs text-slate-400">Confirmar/p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('confirmModal').classList.add('hidden')" class="text-slate-400 hover:text-white transition border-0 bg-transparent">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div class="p-6 space-y-5 bg-slate-50/50">

            <!-- ADVERTENCIA STOCK CRÍTICO -->
            @if (count($alerts) > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-3">
                    <div class="flex items-center gap-2 text-amber-800 font-bold text-xs">
                        <span class="text-base">⚠️</span>
                        <span>Advertencia: Productos en Límite / Stock Crítico</span>
                    </div>
                    <p class="text-[11px] text-amber-700">
                        Los siguientes ítems quedarán con un inventario igual o inferior a su stock mínimo configurado:
                    </p>
                    <div class="bg-white rounded-lg border border-amber-200 overflow-hidden shadow-sm">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-amber-100/60 text-amber-900 font-semibold">
                                <tr>
                                    <th class="p-2">Producto</th>
                                    <th class="p-2 text-center">Actual</th>
                                    <th class="p-2 text-center">Final</th>
                                    <th class="p-2 text-center">Límite Mín.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100">
                                @foreach ($alerts as $alert)
                                    <tr class="hover:bg-amber-50/50">
                                        <td class="p-2 font-medium text-slate-800">{{ $alert['name'] }}</td>
                                        <td class="p-2 text-center text-slate-600">{{ $alert['current_stock'] }}</td>
                                        <td class="p-2 text-center font-bold text-red-600">{{ $alert['final_stock'] }}</td>
                                        <td class="p-2 text-center text-slate-500 font-mono">{{ $alert['minimum_stock'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- FORMULARIO DE CONFIRMACIÓN -->
            <form action="{{ route('invoices.confirm') }}" method="POST" class="space-y-4">
                @csrf
                
                {{-- ⚠️ CAMPOS FALTANTES QUE CAUSABAN EL PROBLEMA DE CABECERA ⚠️ --}}
                <input type="hidden" name="invoice_number" value="{{ $invoiceData['invoice_number'] ?? old('invoice_number', 'N/A') }}">
                <input type="hidden" name="supplier_name" value="{{ $invoiceData['supplier_name'] ?? old('supplier_name', 'Proveedor Desconocido') }}">
                <input type="hidden" name="total_amount" value="{{ $invoiceData['total_amount'] ?? old('total_amount', 0) }}">
                <input type="hidden" name="issue_date" value="{{ $invoiceData['issue_date'] ?? old('issue_date', date('Y-m-d')) }}">
                <input type="hidden" name="temp_file_path" value="{{ $tempFilePath ?? old('temp_file_path', '') }}">

                <!-- Inputs Ocultos (Ítems) -->
                @foreach ($items as $index => $item)
                    @if (isset($item['product_id']))
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] }}">
                        <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                        <input type="hidden" name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}">
                        <input type="hidden" name="items[{{ $index }}][code]" value="{{ $item['code'] ?? '' }}">
                        <input type="hidden" name="items[{{ $index }}][current_stock]" value="{{ $item['current_stock'] ?? 0 }}">
                        <input type="hidden" name="items[{{ $index }}][final_stock]" value="{{ $item['final_stock'] ?? 0 }}">
                    @endif
                @endforeach

                @foreach ($alerts as $index => $alert)
                    <input type="hidden" name="low_stock_alerts[{{ $index }}][name]" value="{{ $alert['name'] }}">
                    <input type="hidden" name="low_stock_alerts[{{ $index }}][current_stock]" value="{{ $alert['current_stock'] }}">
                    <input type="hidden" name="low_stock_alerts[{{ $index }}][final_stock]" value="{{ $alert['final_stock'] }}">
                    <input type="hidden" name="low_stock_alerts[{{ $index }}][minimum_stock]" value="{{ $alert['minimum_stock'] }}">
                @endforeach

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Contraseña de Confirmación
                    </label>
                    <p class="text-[11px] text-slate-500">
                        Ingresa la contraseña de tu cuenta para autorizar la actualización inmediata.
                    </p>
                    <input type="password" 
                           name="password" 
                           required 
                           autofocus 
                           class="w-full mt-2 px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition outline-none" 
                           placeholder="••••••••">
                    
                    @error('password')
                        <span class="text-xs text-red-600 font-bold mt-1 block">
                            ⚠️ {{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Botones del Modal -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" 
                            onclick="document.getElementById('confirmModal').classList.add('hidden')" 
                            style="background-color: #e2e8f0; color: #1e293b; padding: 10px 18px; border-radius: 8px;"
                            class="font-semibold text-xs hover:bg-slate-300 transition cursor-pointer border-0">
                        Cancelar
                    </button>
                    
                    <button type="submit" 
                            style="background-color: #059669; color: #ffffff; padding: 10px 18px; border-radius: 8px;"
                            class="font-bold text-xs shadow-md hover:opacity-90 active:scale-95 transition cursor-pointer border-0 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Autorizar</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>