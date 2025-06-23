<?php

use App\Http\Controllers\CnabFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CnabFileController::class, 'index'])->name('cnab.index');
Route::post('/', [CnabFileController::class, 'upload'])->name('cnab.upload');
