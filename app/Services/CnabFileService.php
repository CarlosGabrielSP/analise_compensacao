<?php

namespace App\Services;

use App\Models\CnabRecord;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CnabFileService
{
    // Mapeamento de códigos de comando
    private array $comandos = [
        '01' => 'Entrada Rejeitada',
        '02' => 'Confirmação de Entrada',
        '06' => 'Liquidação Normal',
        '09' => 'Baixa Automática',
        '10' => 'Baixa por ter sido liquidado',
        '11' => 'Em Ser (Título não existente)',
        '12' => 'Abatimento Concedido',
        '13' => 'Abatimento Cancelado',
        '14' => 'Vencimento Alterado',
        '15' => 'Liquidação em Cartório',
        '16' => 'Confirmação de alteração de juros',
        '17' => 'Liquidação após baixa',
        '18' => 'Acerto de Depositária',
        '19' => 'Instrução de Protesto Rejeitada',
        '20' => 'Instrução de Protesto Cancelada',
        '21' => 'Instrução de Protesto para Título não registrado',
        '22' => 'Instrução de Protesto Processada',
        '23' => 'Inclusão de Ocorrência',
        '24' => 'Retirada de Ocorrência',
        '25' => 'Inclusão de Protesto',
        '26' => 'Tarifa sobre Instrução Cancelada',
        '27' => 'Baixado por Decurso de Prazo',
        '28' => 'Tarifa',
        '29' => 'Ocorrência do Sacado',
        '30' => 'Alteração de Outros Dados',
        '31' => 'Tarifa sobre Instrução',
        '32' => 'Instrução Rejeitada',
        '33' => 'Confirmação de Pedido de Alteração de Outros Dados',
        '34' => 'Retirado de Cartório e Manutenção em Carteira',
        '35' => 'Desagendamento do débito automático',
        '38' => 'Liquidado sem registro (cartão de crédito)',
        '40' => 'Estorno de Pagamento',
        '42' => 'Alteração de Título',
        '43' => 'Relação de Títulos',
        '44' => 'Estorno de Pagamento',
        '45' => 'Alteração de dados',
        '46' => 'Liquidação On-line',
        '47' => 'Estorno de Liquidação On-line',
        '51' => 'Título DDA reconhecido pelo sacado',
        '52' => 'Título DDA não reconhecido pelo sacado',
        '53' => 'Título DDA recusado pela CIP',
        '90' => 'Baixa Automática',
    ];

    // Mapeamento de códigos de natureza de recebimento
    private array $naturezas = [
        '00' => 'Por meio magnético (entrada)',
        '01' => 'Liquidação Normal',
        '02' => 'Por meio magnético (baixa)',
        '03' => 'Acerto nos dados do remetente',
        '04' => 'Acerto nos dados do cedente',
        '05' => 'Acerto nos dados do sacado',
        '06' => 'Acerto nos dados do valor do título',
        '07' => 'Acerto nos dados da emissão do título',
        '08' => 'Acerto nos dados do vencimento do título',
        '09' => 'Acerto nos dados do desconto',
        '10' => 'Acerto nos dados da mora',
        '11' => 'Acerto nos dados da multa',
        '12' => 'Acerto nos dados do abatimento',
        '13' => 'Acerto nos dados do número do título',
        '14' => 'Acerto nos dados da carteira',
        '15' => 'Acerto nos dados da agência cobradora',
        '16' => 'Acerto nos dados do seu número',
        '17' => 'Comando recusado',
        '18' => 'Comando aceito',
        '19' => 'Confirmação de instrução de protesto',
        '20' => 'Confirmação de instrução de sustação de protesto',
        '21' => 'Confirmação de instrução de não protestar',
        '22' => 'Título já baixado',
        '23' => 'Faixa nosso-número excedida (recusa)',
        '24' => 'Título não registrado no sistema',
        '25' => 'Título não aceito pelo sacado',
        '26' => 'Instrução rejeitada',
        '27' => 'Confirmação do pedido de alteração de outros dados',
        '28' => 'Débito de tarifas/custas',
        '29' => 'Ocorrências do sacado',
        '30' => 'Alteração de dados rejeitada',
        '31' => 'Confirmação da alteração dos dados do remetente',
        '32' => 'Confirmação da alteração dos dados do cedente',
        '33' => 'Confirmação da alteração dos dados do sacado',
        '34' => 'Confirmação da alteração dos dados do valor do título',
        '35' => 'Confirmação da alteração dos dados da emissão do título',
        '36' => 'Confirmação da alteração dos dados do vencimento do título',
        '37' => 'Confirmação da alteração dos dados do desconto',
        '38' => 'Confirmação da alteração dos dados da mora',
        '39' => 'Confirmação da alteração dos dados da multa',
        '40' => 'Confirmação da alteração dos dados do abatimento',
        '41' => 'Confirmação da alteração dos dados do número do título',
        '42' => 'Confirmação da alteração dos dados da carteira',
        '43' => 'Confirmação da alteração dos dados da agência cobradora',
        '44' => 'Confirmação da alteração dos dados do seu número',
        '45' => 'Confirmação da alteração de dados - entrada de título',
        '46' => 'Confirmação da alteração de dados - baixa de título',
        '47' => 'Confirmação da alteração de dados - abatimento cancelado',
        '48' => 'Confirmação da alteração de dados - abatimento concedido',
        '49' => 'Confirmação da alteração de dados - prorrogação de vencimento',
        '50' => 'Confirmação da alteração de dados - título protestado',
        '51' => 'Confirmação da alteração de dados - sustar protesto e baixar título',
        '52' => 'Confirmação da alteração de dados - sustar protesto e manter em carteira',
        '53' => 'Título já se encontra na situação pretendida',
    ];

    // Mapeamento de códigos de canal de pagamento
    private array $canaisPagamento = [
        '00' => 'Não identificado',
        '01' => 'Guichê de Caixa',
        '02' => 'Internet Banking',
        '03' => 'Terminais de Autoatendimento',
        '04' => 'Telefone',
        '05' => 'Correspondente Bancário',
        '06' => 'Débito Automático',
        '07' => 'Banco Postal',
        '08' => 'Banco 24 Horas',
        '09' => 'Arquivo-eletrônico',
        '10' => 'Compensação',
        '11' => 'Mobile Banking',
        '12' => 'Cartão de Crédito',
    ];

    /**
     * Processa um arquivo CNAB400/CBR643 e retorna os registros processados
     *
     * @param UploadedFile $file
     * @return array
     */
    public function processFile(UploadedFile $file): array
    {
        $this->deleteRecords();

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

        // Validação básica do arquivo
        if (empty($lines)) {
            return [
                'success' => false,
                'message' => 'Arquivo vazio',
                'records' => []
            ];
        }

        // Log para debug - primeiras 5 linhas
        Log::debug('Análise do arquivo CNAB: ' . $fileName);
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            $line = $lines[$i];
            Log::debug("Linha {$i}: Tamanho=" . strlen($line) . ", Primeiros caracteres: " . substr($line, 0, 20));
        }

        // Verifica se a primeira linha é um Header (tipo 0)
        $firstLine = $lines[0] ?? '';

        // Verifica se o arquivo tem um formato alternativo (alguns bancos podem ter variações)
        $hasHeader = false;
        $hasTrailer = false;

        // Procura por um registro de cabeçalho nas primeiras linhas
        for ($i = 0; $i < min(3, count($lines)); $i++) {
            if (strlen($lines[$i]) >= 1 && substr($lines[$i], 0, 1) === '0') {
                $hasHeader = true;
                $firstLine = $lines[$i];
                break;
            }
        }

        // Se não encontrou um cabeçalho válido
        if (!$hasHeader) {
            return [
                'success' => false,
                'message' => 'Formato de arquivo inválido. Não foi encontrado um registro Header (tipo 0) nas primeiras linhas do arquivo.',
                'records' => []
            ];
        }

        // Verifica se o tamanho da linha é aproximadamente 400 caracteres (com margem de erro)
        if (abs(strlen($firstLine) - 400) > 5) {
            Log::warning('Tamanho da linha de cabeçalho (' . strlen($firstLine) . ') difere do esperado (400)');

            // Se a diferença for muito grande, rejeita o arquivo
            if (abs(strlen($firstLine) - 400) > 20) {
                return [
                    'success' => false,
                    'message' => 'Formato de arquivo inválido. O tamanho da linha de cabeçalho (' . strlen($firstLine) . ') é muito diferente do esperado (400).',
                    'records' => []
                ];
            }
        }

        // Procura por um registro de trailer nas últimas linhas
        for ($i = count($lines) - 1; $i >= max(0, count($lines) - 3); $i--) {
            if (strlen($lines[$i]) >= 1 && substr($lines[$i], 0, 1) === '9') {
                $hasTrailer = true;
                $lastLine = $lines[$i];
                break;
            }
        }

        // Se não encontrou um trailer válido
        if (!$hasTrailer) {
            Log::warning('Trailer não encontrado no arquivo');
            // Continua o processamento mesmo sem trailer, apenas loga o aviso
        }

        // Processa os registros de detalhe (tipo 7)
        $records = [];
        $recordCount = 0;
        $numLinha = 0;
        foreach ($lines as $line) {
            $numLinha++;

            // Ignora linhas vazias ou com tamanho incorreto
            if (empty($line) || strlen($line) !== 400) {
                continue;
            }

            // Processa apenas registros de detalhe (tipo 7)
            if (substr($line, 0, 1) === '7') {
                $recordCount++;

                // Extrai os campos conforme o layout
                $nossoNumero = trim(substr($line, 63, 17));
                $numeroBoleto = trim(substr($line, 116, 10));
                $dataVencimento = $this->parseDate(substr($line, 146, 6));
                $valorBoleto = $this->parseValue(substr($line, 152, 13));
                $dataLiquidacao = $this->parseDate(substr($line, 110, 6));
                $valorRecebido = $this->parseValue(substr($line, 253, 13));
                $comando = substr($line, 108, 2);
                $naturezaRecebimento = substr($line, 86, 2);
                $canalPagamento = substr($line, 392, 2);
                $valorTarifa = $this->parseValue(substr($line, 181, 7));

                // Cria o registro
                $record = new CnabRecord([
                    'file_name' => $fileName,
                    'linha' => $numLinha,
                    'nosso_numero' => $nossoNumero,
                    'numero_boleto' => $numeroBoleto,
                    'data_vencimento' => $dataVencimento,
                    'valor_boleto' => $valorBoleto,
                    'data_liquidacao' => $dataLiquidacao,
                    'valor_recebido' => $valorRecebido,
                    'comando' => $comando,
                    'comando_descricao' => $this->comandos[$comando] ?? 'Desconhecido',
                    'natureza_recebimento' => $naturezaRecebimento,
                    'natureza_recebimento_descricao' => $this->naturezas[$naturezaRecebimento] ?? 'Desconhecido',
                    'canal_pagamento' => $canalPagamento,
                    'canal_pagamento_descricao' => $this->canaisPagamento[$canalPagamento] ?? 'Desconhecido',
                    'valor_tarifa' => $valorTarifa,
                    'raw_data' => $line,
                ]);

                $records[] = $record;
            }
        }

        // Salva os registros no banco de dados
        CnabRecord::insert(
            collect($records)->map(function ($record) {
                return $record->toArray();
            })->toArray()
        );

        return [
            'success' => true,
            'message' => "Arquivo processado com sucesso. {$recordCount} registros encontrados.",
            'records' => $records
        ];
    }

    private function parseDate(string $dateString): ?Carbon
    {
        if (empty($dateString) || $dateString === '000000') {
            return null;
        }

        try {
            $day = substr($dateString, 0, 2);
            $month = substr($dateString, 2, 2);
            $year = substr($dateString, 4, 2);

            // Ajusta o ano para o formato completo (20XX)
            $fullYear = $year < 80 ? "20{$year}" : "19{$year}";

            return Carbon::createFromFormat('d/m/Y', "{$day}/{$month}/{$fullYear}");
        } catch (\Exception $e) {
            Log::error("Erro ao converter data: {$dateString}", ['exception' => $e]);
            return null;
        }
    }

    private function parseValue(string $valueString): float
    {
        // Remove zeros à esquerda
        $valueString = ltrim($valueString, '0');

        // Se a string estiver vazia após remover os zeros, retorna 0
        if (empty($valueString)) {
            return 0;
        }

        // Converte para float com 2 casas decimais
        return floatval(substr($valueString, 0, -2) . '.' . substr($valueString, -2));
    }

    private function deleteRecords() {
        $records = CnabRecord::all();
        foreach ($records as $record) {
            $record->delete();
        }
    }
}
