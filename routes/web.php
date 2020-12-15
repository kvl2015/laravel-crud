<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', [PostController::class, 'index']);
Route::get('/reviewpost', [PostController::class, 'list']);
Route::get('/category/{id}', [PostController::class, 'category']);
Route::get('/post/edit/{id}', [PostController::class, 'edit']);
Route::get('/post/delete/{id}', [PostController::class, 'delete']);
Route::get('/reviewpost', ['uses' => 'App\Http\Controllers\PostController@list', 'as' => 'posts.list']);
Route::get('/post/create', ['uses' => 'App\Http\Controllers\PostController@create', 'as' => 'posts.create']);
Route::put('/post/update/{id}', ['uses' => 'App\Http\Controllers\PostController@update', 'as' => 'posts.update']);
Route::post('/post/store', ['uses' => 'App\Http\Controllers\PostController@store', 'as' => 'posts.store']);
Route::get('/post/{slug}', [PostController::class, 'show']);

Route::get('/dashboard', function () {
    return view('layout');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});