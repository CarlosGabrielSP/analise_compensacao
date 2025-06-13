@extends('layouts.app')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Análise do Arquivo CNAB</h2>
            <a href="{{ route('cnab.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Voltar
            </a>
        </div>

        <!-- Informações do Arquivo -->
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2">Informações do Arquivo</h3>
            <div class="bg-gray-50 p-4 rounded-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><span class="font-medium">Nome do arquivo:</span> {{ $analysis['fileName'] }}</p>
                        <p><span class="font-medium">Tamanho:</span> {{ number_format($analysis['fileSize'] / 1024, 2) }} KB</p>
                    </div>
                    <div>
                        <p><span class="font-medium">Total de linhas:</span> {{ $analysis['totalLines'] }}</p>
                        <p><span class="font-medium">Estrutura:</span> 
                            <span class="@if($analysis['hasHeader']) text-green-600 @else text-red-600 @endif">
                                {{ $analysis['hasHeader'] ? '✓' : '✗' }} Header
                            </span> | 
                            <span class="@if($analysis['hasDetailRecords']) text-green-600 @else text-red-600 @endif">
                                {{ $analysis['hasDetailRecords'] ? '✓' : '✗' }} Registros de Detalhe
                            </span> | 
                            <span class="@if($analysis['hasTrailer']) text-green-600 @else text-red-600 @endif">
                                {{ $analysis['hasTrailer'] ? '✓' : '✗' }} Trailer
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problemas Encontrados -->
        @if(count($analysis['problems']) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2 text-red-600">Problemas Encontrados</h3>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($analysis['problems'] as $problem)
                    <li class="text-red-700">{{ $problem }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Contagem de Tipos de Registro -->
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2">Tipos de Registro</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($analysis['recordTypes'] as $type => $count)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ App\Http\Controllers\CnabFileController::getRecordTypeName($type) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Prévia das Linhas -->
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-2">Prévia das Primeiras Linhas</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Linha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamanho</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Primeiro Caractere</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prévia</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($analysis['lineDetails'] as $detail)
                        <tr class="@if(($detail['length'] != 400 && $detail['length'] > 0) || ($detail['firstChar'] == '0' && !$analysis['hasHeader']) || ($detail['firstChar'] == '9' && !$analysis['hasTrailer'])) bg-red-50 @endif">
                            <td class="px-6 py-4 whitespace-nowrap">{{ $detail['lineNumber'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $detail['type'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap @if($detail['length'] != 400 && $detail['length'] > 0) text-red-600 font-medium @endif">
                                {{ $detail['length'] }}
                                @if($detail['length'] != 400 && $detail['length'] > 0)
                                <span class="text-xs">(Esperado: 400)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $detail['firstChar'] }}</td>
                            <td class="px-6 py-4">
                                <div class="font-mono text-xs overflow-x-auto max-w-lg">
                                    {{ $detail['preview'] }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recomendações -->
        <div class="mt-8">
            <h3 class="text-lg font-medium mb-2">Recomendações</h3>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
                <ul class="list-disc pl-5 space-y-2">
                    <li>O arquivo CNAB400/CBR643 deve começar com um registro Header (tipo 0).</li>
                    <li>Cada linha deve ter exatamente 400 caracteres.</li>
                    <li>O arquivo deve terminar com um registro Trailer (tipo 9).</li>
                    <li>Os registros de detalhe (tipo 7) contêm as informações de transação.</li>
                    <li>Verifique se o arquivo não contém caracteres especiais no início (BOM) ou quebras de linha incorretas.</li>
                    <li>Se o arquivo foi gerado em um sistema Windows, pode conter quebras de linha CRLF (\r\n) que precisam ser convertidas para LF (\n).</li>
                </ul>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="{{ route('cnab.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Voltar
            </a>
        </div>
    </div>
</div>
@endsection
