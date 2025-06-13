@extends('layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Registros de Detalhe (Tipo 7) - {{ $fileName }}</h1>
        <a href="{{ route('cnab.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Voltar</a>
    </div>

    <!-- Resumo e estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-blue-800 mb-2">Resumo Geral</h2>
            <div class="space-y-2">
                <p><span class="font-medium">Total de registros:</span> {{ $estatisticas['total_registros'] }}</p>
                <p><span class="font-medium">Valor total dos boletos:</span> R$ {{ number_format($estatisticas['total_valor_boletos'], 2, ',', '.') }}</p>
                <p><span class="font-medium">Valor total recebido:</span> R$ {{ number_format($estatisticas['total_valor_recebido'], 2, ',', '.') }}</p>
                <p><span class="font-medium">Total de juros/mora:</span> R$ {{ number_format($estatisticas['total_juros_mora'], 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-green-50 p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-green-800 mb-2">Códigos de Comando</h2>
            <div class="max-h-40 overflow-y-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estatisticas['comandos'] as $comando)
                        <tr>
                            <td class="text-sm font-medium text-gray-900">{{ $comando['codigo'] }}</td>
                            <td class="text-sm text-gray-500">{{ $comando['descricao'] }}</td>
                            <td class="text-sm text-gray-900 text-right">{{ $comando['quantidade'] }}</td>
                            <td class="text-sm text-gray-900 text-right">R$ {{ number_format($comando['valor_total'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-yellow-50 p-4 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-yellow-800 mb-2">Canais de Pagamento</h2>
            <div class="max-h-40 overflow-y-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Canal</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estatisticas['canais'] as $canal)
                        <tr>
                            <td class="text-sm font-medium text-gray-900">{{ $canal['codigo'] }}</td>
                            <td class="text-sm text-gray-500">{{ $canal['descricao'] }}</td>
                            <td class="text-sm text-gray-900 text-right">{{ $canal['quantidade'] }}</td>
                            <td class="text-sm text-gray-900 text-right">R$ {{ number_format($canal['valor_total'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabela de registros de detalhe -->
    <div class="overflow-x-auto">
        <table id="detalhesTable" class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nosso Número</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número Boleto</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Boleto</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Liquidação</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Recebido</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Juros/Mora</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comando</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Canal</th>
                    <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($detalhes as $index => $detalhe)
                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="py-2 px-3 text-sm font-medium text-gray-900">{{ $detalhe['nosso_numero'] }}-{{ $detalhe['digito_nosso_numero'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">{{ $detalhe['numero_boleto'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">{{ $detalhe['data_vencimento'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">{{ $detalhe['valor_boleto'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">{{ $detalhe['data_liquidacao'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">{{ $detalhe['valor_recebido'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">{{ $detalhe['juros_mora'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500" title="{{ $detalhe['descricao_comando'] }}">{{ $detalhe['codigo_comando'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500" title="{{ $detalhe['descricao_canal'] }}">{{ $detalhe['canal_pagamento'] }}</td>
                    <td class="py-2 px-3 text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-800" 
                                onclick="mostrarDetalhes('{{ $index }}')"
                                title="Ver detalhes completos">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal para detalhes completos -->
    <div id="detalhesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-xl font-semibold text-gray-700">Detalhes Completos do Registro</h3>
                <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-4">
                <div id="detalhesConteudo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700">Linha Completa:</h4>
                    <div id="linhaCompleta" class="bg-gray-100 p-3 rounded mt-2 overflow-x-auto text-xs font-mono">
                        <!-- Linha completa será preenchida via JavaScript -->
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="fecharModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Inicializa DataTable
    $(document).ready(function() {
        $('#detalhesTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
            },
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
    
    // Dados dos detalhes para o modal
    const detalhes = @json($detalhes);
    
    // Função para mostrar detalhes no modal
    function mostrarDetalhes(index) {
        const detalhe = detalhes[index];
        let html = '';
        
        // Campos principais
        const campos = [
            { label: 'Agência Cedente', valor: detalhe.agencia_cedente + '-' + detalhe.digito_agencia_cedente },
            { label: 'Conta Cedente', valor: detalhe.conta_cedente + '-' + detalhe.digito_conta_cedente },
            { label: 'Nosso Número', valor: detalhe.nosso_numero + '-' + detalhe.digito_nosso_numero },
            { label: 'Número do Boleto', valor: detalhe.numero_boleto },
            { label: 'Data Vencimento', valor: detalhe.data_vencimento },
            { label: 'Valor do Boleto', valor: detalhe.valor_boleto },
            { label: 'Banco Recebedor', valor: detalhe.banco_recebedor },
            { label: 'Agência Recebedora', valor: detalhe.agencia_recebedora + '-' + detalhe.digito_agencia_recebedora },
            { label: 'Espécie', valor: detalhe.especie },
            { label: 'Data Crédito', valor: detalhe.data_credito },
            { label: 'Data Liquidação', valor: detalhe.data_liquidacao },
            { label: 'Valor Recebido', valor: detalhe.valor_recebido },
            { label: 'Juros/Mora', valor: detalhe.juros_mora },
            { label: 'Código Comando', valor: detalhe.codigo_comando + ' - ' + detalhe.descricao_comando },
            { label: 'Natureza', valor: detalhe.natureza_recebimento + ' - ' + detalhe.descricao_natureza },
            { label: 'Canal Pagamento', valor: detalhe.canal_pagamento + ' - ' + detalhe.descricao_canal }
        ];
        
        // Gera HTML para os campos
        campos.forEach(campo => {
            html += `
            <div class="border-b pb-2">
                <span class="text-sm font-medium text-gray-500">${campo.label}:</span>
                <span class="text-sm text-gray-900 ml-2">${campo.valor}</span>
            </div>`;
        });
        
        // Preenche o conteúdo do modal
        document.getElementById('detalhesConteudo').innerHTML = html;
        document.getElementById('linhaCompleta').textContent = detalhe.linha_completa;
        
        // Exibe o modal
        document.getElementById('detalhesModal').classList.remove('hidden');
    }
    
    // Função para fechar o modal
    function fecharModal() {
        document.getElementById('detalhesModal').classList.add('hidden');
    }
</script>
@endpush
