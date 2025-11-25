<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);


Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Link verifikasi baru telah dikirim ke email Anda!']);
})->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');


Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function(Request $request){
        return $request->user();
    });

    Route::apiResource('items', ItemController::class);

    Route::get('/my-claims', [ClaimController::class, 'index']);
    Route::apiResource('claims', ClaimController::class)->except(['index', 'destroy']);

    Route::get('/claims/{claim}/messages', [MessageController::class, 'index']);
    Route::post('/claims/{claim}/messages', [MessageController::class, 'store']);
});

