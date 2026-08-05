<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Procesar Boleta / Factura con IA') }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <!-- Banners de Alerta y Estado -->
        @include('invoices.partials.alerts')

        <!-- Formulario de Carga de Archivo -->
        @include('invoices.partials.upload-form')

        <!-- Resultados y Confirmación (si existen datos) -->
        @if ((isset($processedItems) && count($processedItems) > 0) || old('items'))
            @php
                $items = $processedItems ?? old('items');
                $alerts = $lowStockAlerts ?? old('low_stock_alerts', []);
                $rejected = $isRejected ?? false;
            @endphp

            <!-- Tabla Resumen -->
            @include('invoices.partials.items-table')

            <!-- Modal de Autorización -->
            @include('invoices.partials.confirm-modal')
        @endif

    </div>
</x-app-layout>