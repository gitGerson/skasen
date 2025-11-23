<?php

use Illuminate\Support\Facades\Route;

Route::get('/live', function () {
    return view('welcome');
});
