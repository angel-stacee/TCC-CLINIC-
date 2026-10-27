<?php

use Illuminate\Support\Facades\Route;

// Named login route for auth redirects
Route::get('/login', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200, [
            'Content-Type' => 'text/html',
        ]);
    }
    return view('welcome');
})->name('login');

// Catch-all route to serve the React SPA for all non-API web URLs
Route::get('/{any?}', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200, [
            'Content-Type' => 'text/html',
        ]);
    }
    return view('welcome');
})->where('any', '^(?!api).*$');
