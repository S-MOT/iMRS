<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix("admin")->controller(AdminController::class)->group(function () {
    Route::post("/check-login", "checkLogin");
    Route::post("/re-password", "rePassword");
    Route::get("/get-books-vip", "getBooksVIP");
    Route::post("/admin-approvement", "adminApprovement");
    Route::post("/test-login", "testlogin");
    Route::get("/test", "test");
});

Route::prefix("room")->controller(RoomController::class)->group(function () {
    Route::get("/rooms-list", "roomsList");
});

Route::prefix("book")->controller(BookController::class)->group(function () {
    Route::post("/add-book", "addBook");
    Route::post("/edit-book", "editBook");
    Route::post("/cancel-book", "cancelBook");
    Route::get("/get-book", "getBook");
    Route::get("/get-book/{code}", "getBookByCode");
    Route::get("/get-book-room-id", "getBookByRoomID");
    Route::post("/test-login", "testlogin");
});
