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
        @if (isset($processedItems) && count($processedItems) > 0)
            <div class="p-6 bg-white shadow rounded-lg space-y-6">
                
                <!-- ALERTA VISUAL: Productos que quedan en Stock Mínimo -->
                @if (count($lowStockAlerts) > 0)
                    <div class="p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 rounded-md">
                        <h4 class="font-bold text-md flex items-center gap-2">
                            ⚠️ Advertencia de Stock Crítico
                        </h4>
                        <p class="text-sm mb-2">Los siguientes productos quedarán igual o por debajo de su stock mínimo tras esta operación:</p>
                        <table class="w-full text-left text-xs bg-white rounded border border-amber-200 mt-2">
                            <thead class="bg-amber-100">
                                <tr>
                                    <th class="p-2">Producto</th>
                                    <th class="p-2">Stock Actual</th>
                                    <th class="p-2">Descuento</th>
                                    <th class="p-2">Stock Final</th>
                                    <th class="p-2">Stock Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lowStockAlerts as $alert)
                                    <tr class="border-t border-amber-100">
                                        <td class="p-2 font-medium">{{ $alert['name'] }}</td>
                                        <td class="p-2">{{ $alert['current_stock'] }}</td>
                                        <td class="p-2 text-red-600 font-bold">-{{ $alert['discount'] }}</td>
                                        <td class="p-2 font-bold text-amber-700">{{ $alert['final_stock'] }}</td>
                                        <td class="p-2 text-gray-500">{{ $alert['minimum_stock'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Tabla de Productos a Descontar -->
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
                        @foreach ($processedItems as $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-500">{{ $item['code'] ?? 'N/A' }}</td>
                                <td class="px-3 py-2 font-medium">{{ $item['name'] }}</td>
                                <td class="px-3 py-2 text-center font-bold text-red-600">-{{ $item['quantity'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $item['current_stock'] }}</td>
                                <td class="px-3 py-2 text-center font-bold text-green-600">{{ $item['final_stock'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Botón para abrir el Modal -->
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('confirmModal').classList.remove('hidden')" class="px-6 py-2 bg-green-600 text-white font-bold rounded-md hover:bg-green-700">
                        Confirmar y Descontar Inventario
                    </button>
                </div>
            </div>

            <!-- MODAL DE SEGURIDAD -->
            <div id="confirmModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full space-y-4 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Confirmación de Seguridad</h3>
                    <p class="text-sm text-gray-600">Por favor, ingresa tu contraseña para autorizar el descuento de stock en la bodega.</p>
                    
                    <form action="{{ route('invoices.confirm') }}" method="POST" class="space-y-4">
                        @csrf
                        @foreach ($processedItems as $index => $item)
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] }}">
                            <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                        @endforeach

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <input type="password" name="password" required class="mt-1 block w-full border border-gray-300 rounded-md p-2" placeholder="••••••••">
                            @error('password')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button" onclick="document.getElementById('confirmModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 text-sm">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 text-sm">
                                Autorizar Descuento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>