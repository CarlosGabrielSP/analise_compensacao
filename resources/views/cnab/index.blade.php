@extends('layouts.app')

@section('content')
<div class="bg-white overflow-hidden sm:rounded-lg">
    <div class="mb-8 bg-gray-50 p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Selecione o arquivo</h2>
        <form action="{{ route('cnab.upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-center gap-4">
            @csrf
            <div class="w-full sm:w-auto flex-grow">
                <div class="relative">
                    <input type="file" name="file" id="file" accept=".txt,.bol" class="block w-full border border-gray-300 rounded-lg text-sm text-gray-500
                        file:mr-4 file:py-2.5 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-600 file:text-white
                        hover:file:bg-blue-700 transition-colors duration-200" required>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="process-btn" type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition flex items-center group">
                    Processar Arquivo
                </button>
            </div>
        </form>
        @if($records->count())
            <h2 class="mt-6 text-xl font-semibold text-gray-800"><span class="text-gray-500">Arquivo processado:</span> {{ $filename }}</h2>
        @endif
    </div>

    <livewire:records-table/>
</div>

<!-- Adicionar spinner durante o processamento -->
<div id="processing-spinner" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-gray-700 font-medium">Processando arquivo...</p>
        <p class="text-sm text-gray-500 mt-1">Por favor, aguarde</p>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Adicionar evento para mostrar spinner durante o processamento
        $('form').on('submit', function() {
            $('#processing-spinner').removeClass('hidden');
        });
    });
</script>
@endpush
