<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('BD Vectorial (ChromaDB)') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                Total en VectorDB: {{ $vectorData['total'] ?? 0 }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(isset($vectorData['error']))
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                    <span class="font-medium">⚠️ Error de conexión:</span> No se pudo conectar con el microservicio de IA ({{ $vectorData['error'] }}).
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID Vector</th>
                                    <th scope="col" class="px-6 py-3">N° Boleta / Factura</th>
                                    <th scope="col" class="px-6 py-3">Proveedor</th>
                                    <th scope="col" class="px-6 py-3">Total</th>
                                    <th scope="col" class="px-6 py-3">Texto / Contexto Vectorizado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vectorData['data'] ?? [] as $item)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-mono text-xs">
                                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded">
                                                {{ $item['id'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-900">
                                            {{ $item['metadata']['invoice_number'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $item['metadata']['supplier_name'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-green-600">
                                            ${{ number_format($item['metadata']['total_amount'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <details class="cursor-pointer">
                                                <summary class="text-indigo-600 font-medium text-xs hover:underline">Ver texto indexado</summary>
                                                <div class="mt-2 p-3 bg-gray-50 rounded border text-xs font-mono whitespace-pre-line text-gray-800 max-w-lg">
                                                    {{ $item['document'] }}
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            No hay boletas vectorizadas registradas en ChromaDB.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>