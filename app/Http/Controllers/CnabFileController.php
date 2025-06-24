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

    public function __construct(protected CnabFileService $cnabFileService)
    {
    }

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

//            dd($comandos);

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

        $filename = CnabRecord::first()->file_name ?? null;

        // Obter registros para exibição estática
        $records = CnabRecord::paginate(25);

        return view('cnab.index', compact('comandos', 'naturezas', 'canais', 'records',  'filename'));
    }

    public function upload(Request $request)
    {
        // Validar o arquivo
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt,bol', // max 10MB
        ]);

//        if ($validator->fails()) {
//            return redirect()->back()
//                ->withErrors($validator)
//                ->withInput();
//        }

        // Processar o arquivo
        $result = $this->cnabFileService->processFile($request->file('file'));

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('cnab.index')->with('success', $result['message']);
    }
}
