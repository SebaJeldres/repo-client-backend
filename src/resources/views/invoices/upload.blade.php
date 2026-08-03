<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Procesar Boleta / Factura') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- Banner de ERRORES / RECHAZO DE BOLETA -->
        @if (session('rejected_errors'))
            <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
                <h3 class="font-bold text-lg mb-2">❌ Boleta Rechazada</h3>
                <p class="text-sm mb-2">No se puede procesar el descuento debido a los siguientes problemas:</p>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach (session('rejected_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulario de Subida -->
        <div class="p-6 bg-white shadow rounded-lg">
            <form action="{{ route('invoices.process') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Seleccionar documento (PDF o Imagen)</label>
                    <input type="file" name="invoice_file" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700">
                    Escanear y Validar Boleta
                </button>
            </form>
        </div>

        <!-- RESULTADOS Y CONFIRMACIÓN DE STOCK -->
       @if ((isset($processedItems) && count($processedItems) > 0) || old('items'))
            @php
                // Si la página recargó por error de contraseña, recuperamos las listas desde el request anterior
                $items = $processedItems ?? old('items');
                $alerts = $lowStockAlerts ?? old('low_stock_alerts', []);
            @endphp

            <!-- PANEL DE RESUMEN DE PRODUCTOS -->
            <div class="p-6 bg-white shadow rounded-lg space-y-6">
                <h3 class="text-lg font-bold text-gray-800">Resumen de Productos Aceptados</h3>
                
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Código</th>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-center">Cantidad a Descontar</th>
                            <th class="px-3 py-2 text-center">Stock Actual</th>
                            <th class="px-3 py-2 text-center">Stock Resultante</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($items as $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-500">{{ $item['code'] ?? 'N/A' }}</td>
                                <td class="px-3 py-2 font-medium">{{ $item['name'] ?? 'Producto' }}</td>
                                <td class="px-3 py-2 text-center font-bold text-red-600">-{{ $item['quantity'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $item['current_stock'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center font-bold text-green-600">{{ $item['final_stock'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- BOTÓN PRINCIPAL: Abre el Modal -->
                <div class="flex justify-end pt-4 border-t">
                    <button type="button" onclick="document.getElementById('confirmModal').classList.remove('hidden')" class="px-6 py-2 bg-green-600 text-white font-bold rounded-md hover:bg-green-700 shadow-md transition">
                        Confirmar
                    </button>
                </div>
            </div>

            <!-- MODAL DE SEGURIDAD Y CONFIRMACIÓN -->
            <div id="confirmModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 {{ $errors->has('password') ? '' : 'hidden' }} flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-xl w-full space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
                    
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-2">Confirmación de Operación</h3>

                    <!-- LISTA DE ADVERTENCIAS DENTRO DEL MODAL (Si hay productos que alcanzan o quedan bajo el límite) -->
                    @if (count($alerts) > 0)
                        <div class="p-3 bg-amber-50 border-l-4 border-amber-500 text-amber-800 rounded text-xs space-y-2">
                            <h4 class="font-bold text-sm flex items-center gap-1 text-amber-900">
                                ⚠️ Advertencia: Productos en Stock Crítico / Límite
                            </h4>
                            <p>Los siguientes productos quedarán igual o por debajo de su stock mínimo tras autorizar:</p>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border border-amber-200 bg-white rounded">
                                    <thead class="bg-amber-100 font-semibold">
                                        <tr>
                                            <th class="p-1.5">Producto</th>
                                            <th class="p-1.5 text-center">Actual</th>
                                            <th class="p-1.5 text-center">Final</th>
                                            <th class="p-1.5 text-center">Límite Mín.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-100">
                                        @foreach ($alerts as $alert)
                                            <tr>
                                                <td class="p-1.5 font-medium">{{ $alert['name'] }}</td>
                                                <td class="p-1.5 text-center">{{ $alert['current_stock'] }}</td>
                                                <td class="p-1.5 text-center font-bold text-red-600">{{ $alert['final_stock'] }}</td>
                                                <td class="p-1.5 text-center text-gray-500">{{ $alert['minimum_stock'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <p class="text-sm text-gray-600">
                        Por favor, ingresa tu contraseña para autorizar el descuento del inventario en la base de datos.
                    </p>

                    <!-- FORMULARIO DE CONFIRMACIÓN -->
                    <form action="{{ route('invoices.confirm') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- Inputs ocultos para persisitir los items seleccionados y alertas en caso de fallo -->
                        @foreach ($items as $index => $item)
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] }}">
                            <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                            <input type="hidden" name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}">
                            <input type="hidden" name="items[{{ $index }}][code]" value="{{ $item['code'] ?? '' }}">
                            <input type="hidden" name="items[{{ $index }}][current_stock]" value="{{ $item['current_stock'] ?? 0 }}">
                            <input type="hidden" name="items[{{ $index }}][final_stock]" value="{{ $item['final_stock'] ?? 0 }}">
                        @endforeach

                        @foreach ($alerts as $index => $alert)
                            <input type="hidden" name="low_stock_alerts[{{ $index }}][name]" value="{{ $alert['name'] }}">
                            <input type="hidden" name="low_stock_alerts[{{ $index }}][current_stock]" value="{{ $alert['current_stock'] }}">
                            <input type="hidden" name="low_stock_alerts[{{ $index }}][final_stock]" value="{{ $alert['final_stock'] }}">
                            <input type="hidden" name="low_stock_alerts[{{ $index }}][minimum_stock]" value="{{ $alert['minimum_stock'] }}">
                        @endforeach

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña de Autorización</label>
                            <input type="password" name="password" required autofocus class="mt-1 block w-full border border-gray-300 rounded-md p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••••••">
                            
                            @error('password')
                                <span class="text-xs text-red-600 font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3 pt-3 border-t">
                            <button type="button" onclick="document.getElementById('confirmModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm font-medium">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 text-sm shadow">
                                Autorizar y Actualizar BD
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>