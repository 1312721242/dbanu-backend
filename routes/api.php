<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// routes/api.php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/menu', [MenuController::class, 'index']);


// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/menu', [MenuController::class, 'index']);
//     // Otras rutas protegidas pueden ser definidas aquí
// });



Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
