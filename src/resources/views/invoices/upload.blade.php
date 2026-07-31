<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Procesador Inteligente de Facturas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Formulario de Carga -->
            <div class="p-6 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Subir Documento (PDF o Imagen)</h3>

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('invoices.process') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required 
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-semibold">
                        Analizar con IA
                    </button>
                </form>
            </div>

            <!-- Mostrar Resultados Extraídos por Gemini -->
            @if (isset($invoiceData))
                <div class="p-6 bg-white shadow sm:rounded-lg space-y-4">
                    <h3 class="text-xl font-bold text-gray-800">Resultado de la Extracción</h3>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-md">
                        <div>
                            <span class="font-semibold text-gray-600">Proveedor / Empresa:</span>
                            <p class="text-gray-900 font-bold">{{ $invoiceData['supplier_name'] ?? 'No detectado' }}</p>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">N° Folio / Factura:</span>
                            <p class="text-gray-900 font-bold">{{ $invoiceData['document_number'] ?? 'No detectado' }}</p>
                        </div>
                    </div>

                    <h4 class="text-lg font-semibold text-gray-700 mt-6">Ítems / Productos Detectados</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código / SKU</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Precio Unitario</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Línea</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($invoiceData['items'] as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $item['sku_or_code'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ $item['name'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ $item['quantity'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">${{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 text-right font-bold">${{ number_format($item['total_price'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-2 text-sm text-center text-gray-500">No se encontraron productos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>