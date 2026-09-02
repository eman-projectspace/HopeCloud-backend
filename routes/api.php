<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DonationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/donations', [DonationController::class, 'store']);

    Route::get('/donations', [DonationController::class, 'index']);

    Route::get('/donations/{donation}', [DonationController::class, 'show']);

    Route::put('/donations/{donation}', [DonationController::class, 'update']);

    Route::delete('/donations/{donation}', [DonationController::class, 'destroy']);

});