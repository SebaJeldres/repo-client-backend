<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Crear Nuevo Producto') }}
            </h2>
            <a href="{{ route('products.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded shadow text-sm">
                ← Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('products.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Nombre del Producto -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del Producto <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                value="{{ old('name') }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                                required
                            >
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Código / SKU -->
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                                Código / SKU
                            </label>
                            <input 
                                type="text" 
                                name="code" 
                                id="code" 
                                value="{{ old('code') }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('code') border-red-500 @enderror"
                                placeholder="Ej. PROD-001"
                            >
                            @error('code')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Categoría -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="category_id" 
                                id="category_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('category_id') border-red-500 @enderror"
                                required
                            >
                                <option value="">-- Seleccionar Categoría --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Precio Venta -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                                Precio Venta ($) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                name="price" 
                                id="price" 
                                value="{{ old('price', 0) }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('price') border-red-500 @enderror"
                                required
                            >
                            @error('price')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Precio Costo -->
                        <div>
                            <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-1">
                                Precio Costo ($)
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                name="cost_price" 
                                id="cost_price" 
                                value="{{ old('cost_price') }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('cost_price') border-red-500 @enderror"
                            >
                            @error('cost_price')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stock Inicial -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">
                                Stock Inicial <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="stock" 
                                id="stock" 
                                value="{{ old('stock', 0) }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('stock') border-red-500 @enderror"
                                required
                            >
                            @error('stock')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stock Mínimo -->
                        <div>
                            <label for="minimum_stock" class="block text-sm font-medium text-gray-700 mb-1">
                                Stock Mínimo (Alerta) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="minimum_stock" 
                                id="minimum_stock" 
                                value="{{ old('minimum_stock', 5) }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('minimum_stock') border-red-500 @enderror"
                                required
                            >
                            @error('minimum_stock')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Descripción 
                        </label>
                        <textarea 
                            name="description" 
                            id="description" 
                            rows="3" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Estado (Is Active) -->
                    <div class="mb-6 flex items-center">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            id="is_active" 
                            value="1" 
                            {{ old('is_active', true) ? 'checked' : '' }} 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4"
                        >
                        <label for="is_active" class="ml-2 block text-sm text-gray-900 font-medium">
                            Producto Activo
                        </label>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-end gap-3 border-t pt-4">
                        <a href="{{ route('products.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                            Guardar Producto
                        </button> <!-- Botón de Guardar -->
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>