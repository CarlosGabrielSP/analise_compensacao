<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cnab_records', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->integer('linha');
            $table->string('nosso_numero', 17);
            $table->string('numero_boleto', 10)->nullable();
            $table->date('data_vencimento')->nullable();
            $table->decimal('valor_boleto', 13, 2)->nullable();
            $table->date('data_liquidacao')->nullable();
            $table->decimal('valor_recebido', 13, 2)->nullable();
            $table->string('comando', 2);
            $table->string('comando_descricao')->nullable();
            $table->string('natureza_recebimento', 2);
            $table->string('natureza_recebimento_descricao')->nullable();
            $table->string('canal_pagamento', 2)->nullable();
            $table->string('canal_pagamento_descricao')->nullable();
            $table->decimal('valor_tarifa', 13, 2)->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();

            // Add index for better search performance
            $table->index('nosso_numero');
            $table->index('data_vencimento');
            $table->index('data_liquidacao');
            $table->index('comando');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cnab_records');
    }
};
