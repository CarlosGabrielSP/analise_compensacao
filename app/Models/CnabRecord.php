<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'nosso_numero',
        'numero_boleto',
        'data_vencimento',
        'valor_boleto',
        'data_liquidacao',
        'valor_recebido',
        'comando',
        'comando_descricao',
        'natureza_recebimento',
        'natureza_recebimento_descricao',
        'canal_pagamento',
        'canal_pagamento_descricao',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_vencimento' => 'date',
        'data_liquidacao' => 'date',
        'valor_boleto' => 'decimal:2',
        'valor_recebido' => 'decimal:2',
    ];

    /**
     * Get the formatted valor_boleto attribute.
     *
     * @return string
     */
    public function getFormattedValorBoletoAttribute()
    {
        return 'R$ ' . number_format($this->valor_boleto, 2, ',', '.');
    }

    /**
     * Get the formatted valor_recebido attribute.
     *
     * @return string
     */
    public function getFormattedValorRecebidoAttribute()
    {
        return 'R$ ' . number_format($this->valor_recebido, 2, ',', '.');
    }

    /**
     * Get the formatted data_vencimento attribute.
     *
     * @return string|null
     */
    public function getFormattedDataVencimentoAttribute()
    {
        return $this->data_vencimento ? $this->data_vencimento->format('d/m/Y') : null;
    }

    /**
     * Get the formatted data_liquidacao attribute.
     *
     * @return string|null
     */
    public function getFormattedDataLiquidacaoAttribute()
    {
        return $this->data_liquidacao ? $this->data_liquidacao->format('d/m/Y') : null;
    }
}
