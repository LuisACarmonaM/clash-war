<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarController;

Route::get('/', [WarController::class, 'index'])->name('war.index');
Route::post('/process', [WarController::class, 'processImage'])->name('process');
Route::get('/download', [WarController::class, 'downloadExcel'])->name('download');
Route::post('/clear', [WarController::class, 'clearDatabase'])->name('clear');