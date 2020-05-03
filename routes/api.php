<?php

use App\User;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/*RESOURCES ROUTES FOR VIDEOS*/
Route::get('video/{video}', 'VideoController@show')->name('videoById');
Route::get('video/by/{category}', 'VideoController@index')->name('videoByCategory');
Route::post('video', 'VideoController@store')->name('storeVideo');
Route::patch('video/{video}', 'VideoController@update')->name('updateVideo');
Route::delete('video/{video}', 'VideoController@destroy')->name('destroyVideo');

/*RESOURCES ROUTES FOR COMMENTS*/
Route::post('comment/{video}', 'CommentController@store')->name('storeComment');

/*RESOURCES ROUTES FOR USERS*/

