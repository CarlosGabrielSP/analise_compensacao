<?php

namespace App\Http\Controllers;

use App\Models\CnabRecord;
use App\Services\CnabFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CnabFileController extends Controller
{
    protected $cnabFileService;

    public function __construct(CnabFileService $cnabFileService)
    {
        $this->cnabFileService = $cnabFileService;
    }

    /**
     * Display the file upload form and data table
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Verificar se existem registros na tabela
        $hasRecords = CnabRecord::count() > 0;
        
        // Inicializar coleções vazias para os dropdowns
        $comandos = collect();
        $naturezas = collect();
        $canais = collect();
        
        // Obter os valores únicos para os filtros de dropdown apenas se houver registros
        if ($hasRecords) {
            $comandos = CnabRecord::select('comando', 'comando_descricao')
                ->distinct()
                ->orderBy('comando')
                ->get()
                ->pluck('comando_descricao', 'comando');

            $naturezas = CnabRecord::select('natureza_recebimento', 'natureza_recebimento_descricao')
                ->distinct()
                ->orderBy('natureza_recebimento')
                ->get()
                ->pluck('natureza_recebimento_descricao', 'natureza_recebimento');

            $canais = CnabRecord::select('canal_pagamento', 'canal_pagamento_descricao')
                ->distinct()
                ->orderBy('canal_pagamento')
                ->get()
                ->pluck('canal_pagamento_descricao', 'canal_pagamento');
        }

        return view('cnab.index', compact('comandos', 'naturezas', 'canais', 'hasRecords'));
    }

    /**
     * Process the uploaded file
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        // Validar o arquivo
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt|max:10240', // max 10MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Analisar o formato do arquivo antes de processá-lo
        $fileAnalysis = $this->analyzeFile($request->file('file'));
        if (!$fileAnalysis['valid']) {
            return redirect()->back()->with('error', $fileAnalysis['message']);
        }

        // Processar o arquivo
        $result = $this->cnabFileService->processFile($request->file('file'));

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('cnab.index')->with('success', $result['message']);
    }

    /**
     * Get records for DataTables AJAX request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecords(Request $request)
    {
        $query = CnabRecord::query();

        // Aplicar filtros
        if ($request->has('nosso_numero') && !empty($request->nosso_numero)) {
            $query->where('nosso_numero', 'like', '%' . $request->nosso_numero . '%');
        }

        if ($request->has('numero_boleto') && !empty($request->numero_boleto)) {
            $query->where('numero_boleto', 'like', '%' . $request->numero_boleto . '%');
        }

        // Filtro de data de vencimento
        if ($request->has('data_vencimento_inicio') && !empty($request->data_vencimento_inicio)) {
            $query->where('data_vencimento', '>=', $request->data_vencimento_inicio);
        }

        if ($request->has('data_vencimento_fim') && !empty($request->data_vencimento_fim)) {
            $query->where('data_vencimento', '<=', $request->data_vencimento_fim);
        }

        // Filtro de data de liquidação
        if ($request->has('data_liquidacao_inicio') && !empty($request->data_liquidacao_inicio)) {
            $query->where('data_liquidacao', '>=', $request->data_liquidacao_inicio);
        }

        if ($request->has('data_liquidacao_fim') && !empty($request->data_liquidacao_fim)) {
            $query->where('data_liquidacao', '<=', $request->data_liquidacao_fim);
        }

        // Filtro de valor do boleto
        if ($request->has('valor_boleto_min') && !empty($request->valor_boleto_min)) {
            $query->where('valor_boleto', '>=', $request->valor_boleto_min);
        }

        if ($request->has('valor_boleto_max') && !empty($request->valor_boleto_max)) {
            $query->where('valor_boleto', '<=', $request->valor_boleto_max);
        }

        // Filtro de valor recebido
        if ($request->has('valor_recebido_min') && !empty($request->valor_recebido_min)) {
            $query->where('valor_recebido', '>=', $request->valor_recebido_min);
        }

        if ($request->has('valor_recebido_max') && !empty($request->valor_recebido_max)) {
            $query->where('valor_recebido', '<=', $request->valor_recebido_max);
        }

        // Filtros de seleção múltipla
        if ($request->has('comandos') && !empty($request->comandos)) {
            $query->whereIn('comando', $request->comandos);
        }

        if ($request->has('naturezas') && !empty($request->naturezas)) {
            $query->whereIn('natureza_recebimento', $request->naturezas);
        }

        if ($request->has('canais') && !empty($request->canais)) {
            $query->whereIn('canal_pagamento', $request->canais);
        }

        // Filtro de pesquisa geral
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('nosso_numero', 'like', '%' . $searchValue . '%')
                    ->orWhere('numero_boleto', 'like', '%' . $searchValue . '%')
                    ->orWhere('comando_descricao', 'like', '%' . $searchValue . '%')
                    ->orWhere('natureza_recebimento_descricao', 'like', '%' . $searchValue . '%')
                    ->orWhere('canal_pagamento_descricao', 'like', '%' . $searchValue . '%');
            });
        }

        // Ordenação
        $orderColumn = $request->order[0]['column'] ?? 0;
        $orderDir = $request->order[0]['dir'] ?? 'asc';
        $columns = [
            'nosso_numero',
            'numero_boleto',
            'data_vencimento',
            'valor_boleto',
            'data_liquidacao',
            'valor_recebido',
            'comando_descricao',
            'natureza_recebimento_descricao',
            'canal_pagamento_descricao'
        ];

        $columnName = $columns[$orderColumn] ?? 'nosso_numero';
        $query->orderBy($columnName, $orderDir);

        // Paginação
        $length = $request->length ?? 10;
        $start = $request->start ?? 0;
        
        $total = $query->count();
        $records = $query->offset($start)->limit($length)->get();

        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'nosso_numero' => $record->nosso_numero,
                'numero_boleto' => $record->numero_boleto,
                'data_vencimento' => $record->formatted_data_vencimento,
                'valor_boleto' => $record->formatted_valor_boleto,
                'data_liquidacao' => $record->formatted_data_liquidacao,
                'valor_recebido' => $record->formatted_valor_recebido,
                'comando' => $record->comando . ' - ' . $record->comando_descricao,
                'natureza_recebimento' => $record->natureza_recebimento . ' - ' . $record->natureza_recebimento_descricao,
                'canal_pagamento' => $record->canal_pagamento . ' - ' . $record->canal_pagamento_descricao,
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ]);
    }

    /**
     * Export records to CSV
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = CnabRecord::query();

        // Aplicar os mesmos filtros da tabela
        // (Código de filtro similar ao método getRecords)
        if ($request->has('nosso_numero') && !empty($request->nosso_numero)) {
            $query->where('nosso_numero', 'like', '%' . $request->nosso_numero . '%');
        }

        // Outros filtros...

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cnab_records_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalhos do CSV
            fputcsv($file, [
                'Nosso Número',
                'Número do Boleto',
                'Data de Vencimento',
                'Valor do Boleto',
                'Data de Liquidação',
                'Valor Recebido',
                'Comando',
                'Natureza do Recebimento',
                'Canal de Pagamento',
            ]);

            // Dados
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->nosso_numero,
                    $record->numero_boleto,
                    $record->formatted_data_vencimento,
                    $record->valor_boleto,
                    $record->formatted_data_liquidacao,
                    $record->valor_recebido,
                    $record->comando . ' - ' . $record->comando_descricao,
                    $record->natureza_recebimento . ' - ' . $record->natureza_recebimento_descricao,
                    $record->canal_pagamento . ' - ' . $record->canal_pagamento_descricao,
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Analisa o formato do arquivo CNAB antes de processá-lo
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array
     */
    private function analyzeFile($file)
    {
        $content = file_get_contents($file->getRealPath());
        
        // Remove possível BOM (Byte Order Mark) do início do arquivo
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normaliza quebras de linha para lidar com diferentes formatos (Windows, Unix, Mac)
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Divide o conteúdo em linhas
        $lines = explode("\n", $content);
        
        // Remove linhas vazias
        $lines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });
        
        // Reindexar o array após filtrar
        $lines = array_values($lines);
        
        // Validação básica do arquivo
        if (empty($lines)) {
            return [
                'valid' => false,
                'message' => 'Arquivo vazio'
            ];
        }
        
        // Análise detalhada do arquivo
        $analysis = [
            'total_lines' => count($lines),
            'line_lengths' => [],
            'first_chars' => [],
            'has_header' => false,
            'has_trailer' => false,
            'detail_records' => 0
        ];
        
        // Analisa as primeiras 10 linhas (ou todas, se houver menos)
        $linesToAnalyze = min(10, count($lines));
        for ($i = 0; $i < $linesToAnalyze; $i++) {
            $line = $lines[$i];
            $length = strlen($line);
            $firstChar = substr($line, 0, 1);
            
            $analysis['line_lengths'][$i] = $length;
            $analysis['first_chars'][$i] = $firstChar;
            
            if ($firstChar === '0') {
                $analysis['has_header'] = true;
            } elseif ($firstChar === '9') {
                $analysis['has_trailer'] = true;
            } elseif ($firstChar === '7') {
                $analysis['detail_records']++;
            }
        }
        
        // Verifica também as últimas linhas para encontrar o trailer
        if (!$analysis['has_trailer'] && count($lines) > $linesToAnalyze) {
            for ($i = count($lines) - 3; $i < count($lines); $i++) {
                if ($i >= 0) {
                    $line = $lines[$i];
                    if (strlen($line) > 0 && substr($line, 0, 1) === '9') {
                        $analysis['has_trailer'] = true;
                        break;
                    }
                }
            }
        }
        
        // Log da análise para debug
        \Log::debug('Análise do arquivo CNAB:', $analysis);
        
        // Verifica se o arquivo tem um formato válido
        $isValid = true;
        $message = '';
        
        // Verifica se encontrou um header
        if (!$analysis['has_header']) {
            $isValid = false;
            $message = 'Formato de arquivo inválido. Não foi encontrado um registro Header (tipo 0).';
            
            // Informações adicionais para ajudar no diagnóstico
            $message .= ' Primeiros caracteres encontrados: ';
            foreach (array_slice($analysis['first_chars'], 0, 3) as $index => $char) {
                $message .= "Linha " . ($index + 1) . ": '$char', ";
            }
            
            return [
                'valid' => $isValid,
                'message' => $message,
                'analysis' => $analysis
            ];
        }
        
        // Verifica se os tamanhos das linhas são consistentes
        $expectedLength = 400;
        $inconsistentLength = false;
        
        foreach ($analysis['line_lengths'] as $index => $length) {
            // Permite uma pequena variação no tamanho (até 5 caracteres)
            if (abs($length - $expectedLength) > 5) {
                $inconsistentLength = true;
                break;
            }
        }
        
        if ($inconsistentLength) {
            $isValid = false;
            $message = 'Formato de arquivo inválido. As linhas não têm o tamanho esperado (400 caracteres).';
            
            // Informações adicionais para ajudar no diagnóstico
            $message .= ' Tamanhos encontrados: ';
            foreach (array_slice($analysis['line_lengths'], 0, 3) as $index => $length) {
                $message .= "Linha " . ($index + 1) . ": $length, ";
            }
            
            return [
                'valid' => $isValid,
                'message' => $message,
                'analysis' => $analysis
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Arquivo válido',
            'analysis' => $analysis
        ];
    }

    /**
     * Analisa um arquivo CNAB enviado e exibe informações detalhadas
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function analyzeUploadedFile(Request $request)
    {
        // Validar o arquivo
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt|max:10240', // max 10MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $content = file_get_contents($file->getRealPath());
        
        // Remove possível BOM (Byte Order Mark) do início do arquivo
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normaliza quebras de linha para lidar com diferentes formatos (Windows, Unix, Mac)
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Divide o conteúdo em linhas
        $lines = explode("\n", $content);
        
        // Remove linhas vazias
        $lines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });
        
        // Reindexar o array após filtrar
        $lines = array_values($lines);
        
        // Análise detalhada do arquivo
        $analysis = [
            'fileName' => $fileName,
            'fileSize' => $file->getSize(),
            'totalLines' => count($lines),
            'lineDetails' => [],
            'recordTypes' => [
                '0' => 0, // Header
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
                '6' => 0,
                '7' => 0, // Detalhe
                '8' => 0,
                '9' => 0, // Trailer
            ],
            'hasHeader' => false,
            'hasTrailer' => false,
            'hasDetailRecords' => false,
            'linesByType' => [],
            'problems' => []
        ];
        
        // Analisa cada linha do arquivo
        $maxLinesToShow = min(20, count($lines));
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $length = strlen($line);
            $firstChar = $length > 0 ? substr($line, 0, 1) : '';
            
            // Incrementa o contador para este tipo de registro
            if (isset($analysis['recordTypes'][$firstChar])) {
                $analysis['recordTypes'][$firstChar]++;
            }
            
            // Verifica se é um registro importante
            if ($firstChar === '0') {
                $analysis['hasHeader'] = true;
                $analysis['linesByType']['header'] = $i;
            } elseif ($firstChar === '9') {
                $analysis['hasTrailer'] = true;
                $analysis['linesByType']['trailer'] = $i;
            } elseif ($firstChar === '7') {
                $analysis['hasDetailRecords'] = true;
                if (!isset($analysis['linesByType']['detail'])) {
                    $analysis['linesByType']['detail'] = [];
                }
                $analysis['linesByType']['detail'][] = $i;
            }
            
            // Armazena detalhes das primeiras linhas para exibição
            if ($i < $maxLinesToShow) {
                $analysis['lineDetails'][$i] = [
                    'lineNumber' => $i + 1,
                    'length' => $length,
                    'firstChar' => $firstChar,
                    'preview' => $length > 0 ? substr($line, 0, 50) . '...' : '',
                    'type' => self::getRecordTypeName($firstChar)
                ];
            }
            
            // Verifica problemas com o tamanho da linha
            if ($length > 0 && abs($length - 400) > 5) {
                $analysis['problems'][] = "Linha " . ($i + 1) . " tem tamanho incorreto: $length caracteres (esperado: 400)";
            }
        }
        
        // Verifica problemas estruturais
        if (!$analysis['hasHeader']) {
            $analysis['problems'][] = "Arquivo não possui registro Header (tipo 0)";
        }
        
        if (!$analysis['hasTrailer']) {
            $analysis['problems'][] = "Arquivo não possui registro Trailer (tipo 9)";
        }
        
        if (!$analysis['hasDetailRecords']) {
            $analysis['problems'][] = "Arquivo não possui registros de Detalhe (tipo 7)";
        }
        
        // Verifica se o número de registros no trailer corresponde ao número real
        if ($analysis['hasTrailer'] && isset($analysis['linesByType']['trailer'])) {
            $trailerLine = $lines[$analysis['linesByType']['trailer']];
            if (strlen($trailerLine) >= 393) {
                $recordCountInTrailer = (int)substr($trailerLine, 387, 6);
                $actualDetailCount = $analysis['recordTypes']['7'];
                
                if ($recordCountInTrailer !== $actualDetailCount) {
                    $analysis['problems'][] = "Contagem de registros no Trailer ($recordCountInTrailer) não corresponde ao número real de registros de detalhe ($actualDetailCount)";
                }
            }
        }
        
        // Log para debug
        \Log::debug('Análise do arquivo CNAB:', ['analysis' => $analysis]);
        
        return view('cnab.analyze', compact('analysis'));
    }
    
    /**
     * Retorna o nome do tipo de registro com base no primeiro caractere
     *
     * @param  string  $firstChar
     * @return string
     */
    public static function getRecordTypeName($firstChar)
    {
        $types = [
            '0' => 'Header',
            '1' => 'Transação Tipo 1',
            '2' => 'Transação Tipo 2',
            '3' => 'Transação Tipo 3',
            '4' => 'Transação Tipo 4',
            '5' => 'Transação Tipo 5',
            '6' => 'Transação Tipo 6',
            '7' => 'Detalhe (Registro de Transação)',
            '8' => 'Transação Tipo 8',
            '9' => 'Trailer'
        ];
        
        return $types[$firstChar] ?? 'Desconhecido';
    }

    /**
     * Tabula os registros de detalhe (tipo 7) de um arquivo CNAB
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function tabularDetalhes(Request $request)
    {
        // Validar o arquivo
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt|max:10240', // max 10MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $content = file_get_contents($file->getRealPath());
        
        // Remove possível BOM (Byte Order Mark) do início do arquivo
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normaliza quebras de linha para lidar com diferentes formatos (Windows, Unix, Mac)
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Divide o conteúdo em linhas
        $lines = explode("\n", $content);
        
        // Remove linhas vazias
        $lines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });
        
        // Reindexar o array após filtrar
        $lines = array_values($lines);
        
        // Filtrar apenas as linhas que começam com 7 (registros de detalhe)
        $detalhes = [];
        foreach ($lines as $line) {
            if (strlen($line) > 0 && substr($line, 0, 1) === '7') {
                // Extrair os campos do registro de detalhe conforme layout CNAB400/CBR643
                $detalhe = [
                    'tipo_registro' => substr($line, 0, 1),
                    'agencia_cedente' => substr($line, 1, 4),
                    'digito_agencia_cedente' => substr($line, 5, 1),
                    'razao_conta_cedente' => substr($line, 6, 8),
                    'conta_cedente' => substr($line, 14, 7),
                    'digito_conta_cedente' => substr($line, 21, 1),
                    'nosso_numero' => substr($line, 62, 11),
                    'digito_nosso_numero' => substr($line, 73, 1),
                    'numero_boleto' => substr($line, 116, 10),
                    'data_vencimento' => $this->formatarData(substr($line, 146, 6)),
                    'valor_boleto' => $this->formatarValor(substr($line, 152, 13)),
                    'banco_recebedor' => substr($line, 165, 3),
                    'agencia_recebedora' => substr($line, 168, 4),
                    'digito_agencia_recebedora' => substr($line, 172, 1),
                    'especie' => substr($line, 173, 2),
                    'data_credito' => $this->formatarData(substr($line, 175, 6)),
                    'valor_recebido' => $this->formatarValor(substr($line, 253, 13)),
                    'juros_mora' => $this->formatarValor(substr($line, 266, 13)),
                    'data_liquidacao' => $this->formatarData(substr($line, 295, 6)),
                    'codigo_comando' => substr($line, 108, 2),
                    'descricao_comando' => $this->getDescricaoComando(substr($line, 108, 2)),
                    'natureza_recebimento' => substr($line, 86, 2),
                    'descricao_natureza' => $this->getDescricaoNatureza(substr($line, 86, 2)),
                    'canal_pagamento' => substr($line, 392, 2),
                    'descricao_canal' => $this->getDescricaoCanal(substr($line, 392, 2)),
                    'linha_completa' => $line
                ];
                
                $detalhes[] = $detalhe;
            }
        }
        
        // Estatísticas
        $estatisticas = [
            'total_registros' => count($detalhes),
            'total_valor_boletos' => array_sum(array_map(function($d) { 
                return $this->converterParaFloat($d['valor_boleto']); 
            }, $detalhes)),
            'total_valor_recebido' => array_sum(array_map(function($d) { 
                return $this->converterParaFloat($d['valor_recebido']); 
            }, $detalhes)),
            'total_juros_mora' => array_sum(array_map(function($d) { 
                return $this->converterParaFloat($d['juros_mora']); 
            }, $detalhes)),
            'comandos' => [],
            'naturezas' => [],
            'canais' => []
        ];
        
        // Contagem de comandos, naturezas e canais
        foreach ($detalhes as $detalhe) {
            $cmd = $detalhe['codigo_comando'];
            $nat = $detalhe['natureza_recebimento'];
            $canal = $detalhe['canal_pagamento'];
            
            if (!isset($estatisticas['comandos'][$cmd])) {
                $estatisticas['comandos'][$cmd] = [
                    'codigo' => $cmd,
                    'descricao' => $detalhe['descricao_comando'],
                    'quantidade' => 0,
                    'valor_total' => 0
                ];
            }
            $estatisticas['comandos'][$cmd]['quantidade']++;
            $estatisticas['comandos'][$cmd]['valor_total'] += $this->converterParaFloat($detalhe['valor_recebido']);
            
            if (!isset($estatisticas['naturezas'][$nat])) {
                $estatisticas['naturezas'][$nat] = [
                    'codigo' => $nat,
                    'descricao' => $detalhe['descricao_natureza'],
                    'quantidade' => 0,
                    'valor_total' => 0
                ];
            }
            $estatisticas['naturezas'][$nat]['quantidade']++;
            $estatisticas['naturezas'][$nat]['valor_total'] += $this->converterParaFloat($detalhe['valor_recebido']);
            
            if (!isset($estatisticas['canais'][$canal])) {
                $estatisticas['canais'][$canal] = [
                    'codigo' => $canal,
                    'descricao' => $detalhe['descricao_canal'],
                    'quantidade' => 0,
                    'valor_total' => 0
                ];
            }
            $estatisticas['canais'][$canal]['quantidade']++;
            $estatisticas['canais'][$canal]['valor_total'] += $this->converterParaFloat($detalhe['valor_recebido']);
        }
        
        return view('cnab.tabular-detalhes', compact('detalhes', 'estatisticas', 'fileName'));
    }
    
    /**
     * Converte um valor formatado para float
     *
     * @param  string  $valor
     * @return float
     */
    private function converterParaFloat($valor)
    {
        // Remove formatação e converte para float
        return (float) preg_replace('/[^0-9,.]/', '', str_replace(',', '.', $valor));
    }

    /**
     * Formata uma data no formato DDMMAA para DD/MM/AAAA
     *
     * @param  string  $data
     * @return string
     */
    private function formatarData($data)
    {
        if (empty($data) || strlen(trim($data)) != 6) {
            return '';
        }
        
        $dia = substr($data, 0, 2);
        $mes = substr($data, 2, 2);
        $ano = substr($data, 4, 2);
        
        // Ajusta o ano para o formato de 4 dígitos
        $anoAtual = date('Y');
        $seculo = substr($anoAtual, 0, 2);
        $anoCompleto = $seculo . $ano;
        
        // Se o ano resultante for mais de 10 anos no futuro, assume o século anterior
        if ((int)$anoCompleto > ((int)$anoAtual + 10)) {
            $anoCompleto = ((int)$seculo - 1) . $ano;
        }
        
        return "$dia/$mes/$anoCompleto";
    }
    
    /**
     * Formata um valor numérico para formato monetário
     *
     * @param  string  $valor
     * @return string
     */
    private function formatarValor($valor)
    {
        // Converte para float (divide por 100 para considerar os centavos)
        $valorFloat = (float)$valor / 100;
        
        // Formata com duas casas decimais
        return 'R$ ' . number_format($valorFloat, 2, ',', '.');
    }
    
    /**
     * Retorna a descrição do código de comando
     *
     * @param  string  $codigo
     * @return string
     */
    private function getDescricaoComando($codigo)
    {
        $comandos = [
            '01' => 'Entrada Confirmada',
            '02' => 'Entrada Rejeitada',
            '03' => 'Entrada Rejeitada - CEP Irregular',
            '04' => 'Entrada Rejeitada - CEP Irregular',
            '05' => 'Entrada Rejeitada - CEP Irregular',
            '06' => 'Liquidação Normal',
            '07' => 'Liquidação por Conta',
            '08' => 'Liquidação por Saldo',
            '09' => 'Baixa Automática',
            '10' => 'Baixa por ter sido liquidado',
            '11' => 'Em Ser',
            '12' => 'Abatimento Concedido',
            '13' => 'Abatimento Cancelado',
            '14' => 'Vencimento Alterado',
            '15' => 'Liquidação em Cartório',
            '16' => 'Confirmação de alteração de juros de mora',
            '17' => 'Confirmação de alteração do valor de desconto',
            '18' => 'Confirmação de alteração do valor de IOF',
            '19' => 'Confirmação de alteração do valor de abatimento',
            '20' => 'Confirmação de alteração de outros dados',
            '21' => 'Cobrança a creditar (liquidação em trânsito)',
            '22' => 'Confirmação de recebimento de instrução de não protestar',
            '23' => 'Protesto enviado a cartório',
            '24' => 'Instrução de protesto sustada',
            '25' => 'Alegações do pagador',
            '26' => 'Tarifa de aviso de cobrança',
            '27' => 'Tarifa de extrato posição',
            '28' => 'Tarifa de relação das liquidações',
            '29' => 'Tarifa de manutenção de títulos vencidos',
            '30' => 'Débito de tarifas',
            '31' => 'Baixado por ter sido protestado',
            '32' => 'Instrução rejeitada',
            '33' => 'Confirmação de pedido de alteração de outros dados',
            '34' => 'Retirado do cartório e manutenção em carteira',
            '35' => 'Aceite do pagador',
            '36' => 'Liquidação de título não registrado',
            '37' => 'Confirmação de recebimento de instrução de não cobrar juros',
            '38' => 'Confirmação de recebimento de instrução de dispensa de multa',
            '39' => 'Confirmação de recebimento de instrução de dispensa de indexador',
            '40' => 'Confirmação de recebimento de instrução de dispensa de prazo limite de recebimento',
            '41' => 'Confirmação de recebimento de instrução para conceder desconto',
            '42' => 'Confirmação de recebimento de instrução de alterar nome do pagador',
            '43' => 'Confirmação de recebimento de instrução de alterar endereço do pagador',
            '44' => 'Confirmação de recebimento de instrução de alterar cidade do pagador',
            '45' => 'Confirmação de recebimento de instrução de alterar UF do pagador',
            '46' => 'Instrução para cancelar protesto confirmada',
            '47' => 'Instrução para protesto para fins falimentares confirmada',
            '48' => 'Confirmação de recebimento de instrução de transferência de carteira/modalidade de cobrança',
            '49' => 'Alteração de contrato de cobrança',
            '50' => 'Título pago com cheque devolvido',
            '51' => 'Título DDA reconhecido pelo pagador',
            '52' => 'Título DDA não reconhecido pelo pagador',
            '53' => 'Título DDA recusado pela CIP',
            '54' => 'Confirmação de instrução de baixa de título negativado sem protesto',
            '55' => 'Confirmação de pedido de dispensa de multa',
            '56' => 'Confirmação de pedido de dispensa de juros de mora',
            '57' => 'Confirmação de pedido de dispensa de desconto',
            '98' => 'Instrução de protesto processada',
            '99' => 'Remessa rejeitada',
        ];
        
        return $comandos[$codigo] ?? "Código $codigo";
    }
    
    /**
     * Retorna a descrição da natureza de recebimento
     *
     * @param  string  $codigo
     * @return string
     */
    private function getDescricaoNatureza($codigo)
    {
        $naturezas = [
            '01' => 'Liquidação em dinheiro',
            '02' => 'Liquidação em cheque',
            '03' => 'Liquidação por meio eletrônico',
            '04' => 'Compensação eletrônica',
            '05' => 'Liquidação em banco correspondente',
            '06' => 'Liquidação conforme instrução',
            '07' => 'Liquidação após baixa ou não registro',
            '08' => 'Liquidação em cartório',
            '09' => 'Liquidação comandada pelo cliente',
            '10' => 'Liquidação comandada pelo banco correspondente',
            '11' => 'Liquidação pelo valor mínimo',
            '12' => 'Liquidação pelo valor máximo',
            '13' => 'Liquidação pelo valor médio',
            '14' => 'Liquidação pelo primeiro valor',
            '15' => 'Liquidação pelo último valor',
            '16' => 'Liquidação pelo maior valor',
            '17' => 'Liquidação pelo menor valor',
        ];
        
        return $naturezas[$codigo] ?? "Código $codigo";
    }
    
    /**
     * Retorna a descrição do canal de pagamento
     *
     * @param  string  $codigo
     * @return string
     */
    private function getDescricaoCanal($codigo)
    {
        $canais = [
            '00' => 'Não identificado',
            '01' => 'Caixa eletrônico',
            '02' => 'Internet banking',
            '03' => 'Terminal de autoatendimento',
            '04' => 'Telefone (URA)',
            '05' => 'Correspondente bancário',
            '06' => 'Agência bancária',
            '07' => 'Mobile banking',
            '08' => 'Débito automático',
            '09' => 'Arrecadação externa',
            '10' => 'PIX',
            '11' => 'Cartão de crédito',
            '12' => 'Cartão de débito',
            '13' => 'Compensação',
            '14' => 'Banco postal',
            '15' => 'Lotérico',
            '16' => 'Aplicativo do banco',
            '17' => 'Whatsapp',
            '18' => 'API/Integração',
            '99' => 'Outros',
        ];
        
        return $canais[$codigo] ?? "Código $codigo";
    }
}
