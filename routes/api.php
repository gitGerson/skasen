<?php

use App\Http\Controllers\AspirasiController;
use Illuminate\Support\Facades\Route;

Route::post('/aspirasi/classify', [AspirasiController::class, 'classify']);
