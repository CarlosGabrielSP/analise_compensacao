<?php

use App\Http\Controllers\CnabFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CnabFileController::class, 'index'])->name('cnab.index');
Route::post('/upload', [CnabFileController::class, 'upload'])->name('cnab.upload');
Route::get('/records', [CnabFileController::class, 'getRecords'])->name('cnab.records');
Route::get('/export', [CnabFileController::class, 'export'])->name('cnab.export');
Route::post('/analyze', [CnabFileController::class, 'analyzeUploadedFile'])->name('cnab.analyze');
Route::post('/tabular-detalhes', [CnabFileController::class, 'tabularDetalhes'])->name('cnab.tabular-detalhes');
