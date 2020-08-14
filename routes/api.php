<?php

use App\Http\Controllers\FileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', 'UserController@register');
Route::post('login', 'UserController@login');
Route::get('files/{id}', 'FileController@index');
Route::get('file/{id}', 'FileController@show');
Route::post('file', 'FileController@store');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
