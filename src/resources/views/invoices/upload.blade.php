<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Procesar Boleta / Factura con IA') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- MENSAJE DE ÉXITO -->
        @if (isset($success) && $success)
            <div class="p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-lg shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✅</span>
                    <p class="font-semibold text-sm">{{ $success }}</p>
                </div>
            </div>
        @endif

        <!-- BANNER DE ERRORES / RECHAZO DE BOLETA -->
        @if ((isset($rejectedErrors) && count($rejectedErrors) > 0) || session('rejected_errors'))
            @php
                $errorsList = $rejectedErrors ?? session('rejected_errors');
            @endphp
            <div class="p-5 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-xl shadow-sm space-y-2">
                <div class="flex items-center gap-2 font-bold text-base text-red-900">
                    <span class="text-xl">❌</span>
                    <h3>Boleta Rechazada</h3>
                </div>
                <p class="text-xs text-red-700">No se puede procesar el descuento en inventario debido a los siguientes problemas:</p>
                <ul class="list-disc pl-5 text-xs space-y-1 text-red-700">
                    @foreach ($errorsList as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ERROR DE EXCEPCIÓN O SISTEMA -->
        @if ($errors->has('error'))
            <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm text-xs font-semibold">
                ⚠️ {{ $errors->first('error') }}
            </div>
        @endif

        <!-- FORMULARIO DE SUBIDA DE DOCUMENTO -->
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

        <!-- RESULTADOS Y CONFIRMACIÓN DE STOCK -->
        @if ((isset($processedItems) && count($processedItems) > 0) || old('items'))
            @php
                $items = $processedItems ?? old('items');
                $alerts = $lowStockAlerts ?? old('low_stock_alerts', []);
                $rejected = $isRejected ?? false;
            @endphp

            <!-- PANEL DE RESUMEN DE PRODUCTOS -->
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

                <!-- BOTÓN PRINCIPAL (Deshabilitado si la boleta fue rechazada) -->
                <div class="flex justify-end pt-4 border-t border-gray-100">
                    @if (!$rejected)
                        <button type="button" 
                                onclick="document.getElementById('confirmModal').classList.remove('hidden')" 
                                style="background-color: #059669; color: #ffffff; padding: 12px 24px; border-radius: 8px; line-height: 1.5;"
                                class="inline-flex items-center gap-2 font-bold text-xs shadow-md hover:opacity-90 active:scale-95 transition cursor-pointer border-0">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Confirmar y Procesar Descuento</span>
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

            <!-- MODAL DE AUTORIZACIÓN -->
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
                                <p class="text-xs text-slate-400">Confirmación de descuento en base de datos</p>
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
                            
                            <!-- Datos ocultos para enviar los productos -->
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
                                    Ingresa la contraseña de tu cuenta para autorizar la actualización inmediata en la base de datos.
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
                                    <span>Autorizar y Actualizar BD</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        @endif

    </div>
</x-app-layout>