<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adminController;
use App\Http\Controllers\BookController;
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

Route::prefix("admin")->controller(adminController::class)->group(function () {
    Route::post("/", "checkLogin");
    // Route::post("/", "rePassword");
    // Route::get("/", "getBooksVIP");
    // Route::post("/", "adminApprovement");
});

Route::prefix("room")->controller(RoomController::class)->group(function () {
    Route::get("/rooms-list", "roomsList2");
});

Route::prefix("book")->controller(BookController::class)->group(function () {
    // Route::get("/", "add-book");
    // Route::get("/", "edit-book");
    // Route::get("/", "cancel-book");
    // Route::get("/", "get-book");
    // Route::get("/", "get-book-room-id");
    // Route::get("/", "get-book-history");
});
