<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\testController;
use App\Http\Controllers\adminController;
use App\Http\Controllers\RoomController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix("test")->controller(testController::class)->group(function () {
    Route::get("/", "getAll");
});


Route::prefix("check-login")->controller(adminController::class)->group(function () {
    Route::post("/", "checkLogin");
    // Route::post("/check-login", "checkLogin");
});

Route::prefix("room")->controller(RoomController::class)->group(function () {
    Route::get("/rooms-list", "roomsList");
});