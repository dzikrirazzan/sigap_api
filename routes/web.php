<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'SIGAP Emergency API',
    ]);
});

Route::get('/login', function () {
    return redirect('/');
})->name('login');
