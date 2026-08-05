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