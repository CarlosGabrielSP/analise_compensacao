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
        Schema::create('cnab_header', function (Blueprint $table) {
            $table->id();
            $table->string('nome_arquivo')->unique();
            $table->string('tipo_operacao');
            $table->string('tipo_servico');
            $table->integer('numero_conta');
            $table->integer('agencia');
            $table->date('data_gravacao');
            $table->string('codigo_banco');
            $table->string('nome_cedente');
            $table->string('cidade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cnab_header');
    }
};
