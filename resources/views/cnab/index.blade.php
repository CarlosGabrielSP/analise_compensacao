@extends('layouts.app')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <!-- Upload Form -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Upload de Arquivo CNAB400/CBR643</h2>
            <form action="{{ route('cnab.upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                @csrf
                <div class="w-full sm:w-auto">
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Selecione o arquivo (.txt)</label>
                    <input type="file" name="file" id="file" accept=".txt" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100" required>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Processar Arquivo
                    </button>
                    <button type="button" id="analyze-btn" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                        Analisar Formato
                    </button>
                    <button type="button" id="tabular-btn" class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Tabular Registros Detalhe (Tipo 7)
                    </button>
                </div>
            </form>
            <div class="mt-2 text-sm text-gray-500">
                <p>O arquivo deve estar no formato CNAB400/CBR643 com registros de 400 bytes.</p>
                <p class="mt-1">Se estiver tendo problemas com o formato do arquivo, use a opção "Analisar Formato" para verificar a estrutura.</p>
            </div>
        </div>

        @if(!isset($hasRecords) || $hasRecords)
        <!-- Export Button -->
        <div class="mb-4 flex justify-end">
            <button id="search-btn" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 mr-2">
                Filtros Avançados
            </button>
            <a href="{{ route('cnab.export') }}" id="export-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Exportar CSV
            </a>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table id="cnab-table" class="min-w-full divide-y divide-gray-200 stripe hover" style="width:100%">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nosso Número</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número do Boleto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Vencimento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor do Boleto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Liquidação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Recebido</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comando</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Natureza do Recebimento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Canal de Pagamento</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Nenhum registro encontrado. Faça o upload de um arquivo CNAB400/CBR643 para visualizar os dados.
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Advanced Search Modal -->
<div id="search-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" aria-modal="true">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3">
            <h3 class="text-lg font-medium">Filtros Avançados</h3>
            <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Nosso Número -->
            <div>
                <label for="filter_nosso_numero" class="block text-sm font-medium text-gray-700 mb-1">Nosso Número</label>
                <input type="text" id="filter_nosso_numero" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Número do Boleto -->
            <div>
                <label for="filter_numero_boleto" class="block text-sm font-medium text-gray-700 mb-1">Número do Boleto</label>
                <input type="text" id="filter_numero_boleto" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Data de Vencimento -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento</label>
                <div class="flex space-x-2">
                    <div class="w-1/2">
                        <input type="text" id="filter_data_vencimento_inicio" placeholder="De" class="datepicker block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="w-1/2">
                        <input type="text" id="filter_data_vencimento_fim" placeholder="Até" class="datepicker block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Data de Liquidação -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Liquidação</label>
                <div class="flex space-x-2">
                    <div class="w-1/2">
                        <input type="text" id="filter_data_liquidacao_inicio" placeholder="De" class="datepicker block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="w-1/2">
                        <input type="text" id="filter_data_liquidacao_fim" placeholder="Até" class="datepicker block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Valor do Boleto -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor do Boleto</label>
                <div class="flex space-x-2">
                    <div class="w-1/2">
                        <input type="number" id="filter_valor_boleto_min" placeholder="Mínimo" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="w-1/2">
                        <input type="number" id="filter_valor_boleto_max" placeholder="Máximo" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Valor Recebido -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor Recebido</label>
                <div class="flex space-x-2">
                    <div class="w-1/2">
                        <input type="number" id="filter_valor_recebido_min" placeholder="Mínimo" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="w-1/2">
                        <input type="number" id="filter_valor_recebido_max" placeholder="Máximo" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Comando -->
            <div>
                <label for="filter_comandos" class="block text-sm font-medium text-gray-700 mb-1">Comando</label>
                <select id="filter_comandos" multiple class="select2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($comandos as $codigo => $descricao)
                        <option value="{{ $codigo }}">{{ $codigo }} - {{ $descricao }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Natureza do Recebimento -->
            <div>
                <label for="filter_naturezas" class="block text-sm font-medium text-gray-700 mb-1">Natureza do Recebimento</label>
                <select id="filter_naturezas" multiple class="select2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($naturezas as $codigo => $descricao)
                        <option value="{{ $codigo }}">{{ $codigo }} - {{ $descricao }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Canal de Pagamento -->
            <div>
                <label for="filter_canais" class="block text-sm font-medium text-gray-700 mb-1">Canal de Pagamento</label>
                <select id="filter_canais" multiple class="select2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($canais as $codigo => $descricao)
                        <option value="{{ $codigo }}">{{ $codigo }} - {{ $descricao }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <button id="reset-filters" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                Limpar Filtros
            </button>
            <button id="apply-filters" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Aplicar Filtros
            </button>
        </div>
    </div>
</div>

<!-- Formulário oculto para análise de arquivo -->
<form id="analyze-form" action="{{ route('cnab.analyze') }}" method="POST" enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="file" name="file" id="analyze-file">
</form>

<!-- Formulário oculto para tabular detalhes -->
<form id="tabular-form" action="{{ route('cnab.tabular-detalhes') }}" method="POST" enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="file" name="file" id="tabular-file">
</form>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize date pickers
        $('.datepicker').flatpickr({
            dateFormat: 'd/m/Y',
            locale: 'pt',
            allowInput: true
        });

        // Initialize select2 for multiple select
        $('.select2').select2({
            placeholder: 'Selecione opções',
            width: '100%'
        });

        // Botão de análise de arquivo
        $('#analyze-btn').on('click', function() {
            var fileInput = $('#file')[0];
            if (fileInput.files.length === 0) {
                alert('Por favor, selecione um arquivo para analisar.');
                return;
            }
            
            // Copia o arquivo selecionado para o formulário de análise
            var analyzeFileInput = $('#analyze-file')[0];
            analyzeFileInput.files = fileInput.files;
            
            // Envia o formulário de análise
            $('#analyze-form').submit();
        });

        // Botão para tabular detalhes
        $('#tabular-btn').on('click', function() {
            var fileInput = $('#file')[0];
            if (fileInput.files.length === 0) {
                alert('Por favor, selecione um arquivo para tabular detalhes.');
                return;
            }
            
            // Copia o arquivo selecionado para o formulário de tabular detalhes
            var tabularFileInput = $('#tabular-file')[0];
            tabularFileInput.files = fileInput.files;
            
            // Envia o formulário de tabular detalhes
            $('#tabular-form').submit();
        });

        @if(!isset($hasRecords) || $hasRecords)
        // Initialize DataTable
        var table = $('#cnab-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("cnab.records") }}',
                data: function(d) {
                    // Add custom filters
                    d.nosso_numero = $('#filter_nosso_numero').val();
                    d.numero_boleto = $('#filter_numero_boleto').val();
                    d.data_vencimento_inicio = $('#filter_data_vencimento_inicio').val();
                    d.data_vencimento_fim = $('#filter_data_vencimento_fim').val();
                    d.data_liquidacao_inicio = $('#filter_data_liquidacao_inicio').val();
                    d.data_liquidacao_fim = $('#filter_data_liquidacao_fim').val();
                    d.valor_boleto_min = $('#filter_valor_boleto_min').val();
                    d.valor_boleto_max = $('#filter_valor_boleto_max').val();
                    d.valor_recebido_min = $('#filter_valor_recebido_min').val();
                    d.valor_recebido_max = $('#filter_valor_recebido_max').val();
                    d.comandos = $('#filter_comandos').val();
                    d.naturezas = $('#filter_naturezas').val();
                    d.canais = $('#filter_canais').val();
                }
            },
            columns: [
                { data: 'nosso_numero', name: 'nosso_numero' },
                { data: 'numero_boleto', name: 'numero_boleto' },
                { data: 'data_vencimento', name: 'data_vencimento' },
                { data: 'valor_boleto', name: 'valor_boleto' },
                { data: 'data_liquidacao', name: 'data_liquidacao' },
                { data: 'valor_recebido', name: 'valor_recebido' },
                { data: 'comando', name: 'comando' },
                { data: 'natureza_recebimento', name: 'natureza_recebimento' },
                { data: 'canal_pagamento', name: 'canal_pagamento' }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
        });

        // Search button
        $('#search-btn').on('click', function() {
            $('#search-modal').removeClass('hidden');
        });

        // Close modal
        $('#close-modal').on('click', function() {
            $('#search-modal').addClass('hidden');
        });

        // Apply filters
        $('#apply-filters').on('click', function() {
            table.ajax.reload();
            $('#search-modal').addClass('hidden');
        });

        // Reset filters
        $('#reset-filters').on('click', function() {
            // Clear all inputs
            $('#filter_nosso_numero').val('');
            $('#filter_numero_boleto').val('');
            $('#filter_data_vencimento_inicio').val('');
            $('#filter_data_vencimento_fim').val('');
            $('#filter_data_liquidacao_inicio').val('');
            $('#filter_data_liquidacao_fim').val('');
            $('#filter_valor_boleto_min').val('');
            $('#filter_valor_boleto_max').val('');
            $('#filter_valor_recebido_min').val('');
            $('#filter_valor_recebido_max').val('');
            $('#filter_comandos').val(null).trigger('change');
            $('#filter_naturezas').val(null).trigger('change');
            $('#filter_canais').val(null).trigger('change');
            
            // Reload table
            table.ajax.reload();
        });

        // Update export URL with current filters
        $('#export-btn').on('click', function(e) {
            e.preventDefault();
            
            var params = $.param({
                nosso_numero: $('#filter_nosso_numero').val(),
                numero_boleto: $('#filter_numero_boleto').val(),
                data_vencimento_inicio: $('#filter_data_vencimento_inicio').val(),
                data_vencimento_fim: $('#filter_data_vencimento_fim').val(),
                data_liquidacao_inicio: $('#filter_data_liquidacao_inicio').val(),
                data_liquidacao_fim: $('#filter_data_liquidacao_fim').val(),
                valor_boleto_min: $('#filter_valor_boleto_min').val(),
                valor_boleto_max: $('#filter_valor_boleto_max').val(),
                valor_recebido_min: $('#filter_valor_recebido_min').val(),
                valor_recebido_max: $('#filter_valor_recebido_max').val(),
                comandos: $('#filter_comandos').val(),
                naturezas: $('#filter_naturezas').val(),
                canais: $('#filter_canais').val()
            });
            
            window.location.href = '{{ route("cnab.export") }}?' + params;
        });
        @endif
    });
</script>
@endpush
