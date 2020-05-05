<?php

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

/*RESOURCES ROUTES FOR VIDEOS*/
Route::get('video/{video}', 'VideoController@show')->name('videoById');
Route::get('video/by/{category}', 'VideoController@index')->name('videoByCategory');
Route::post('video', 'VideoController@store')->name('storeVideo');
Route::patch('video/{video}', 'VideoController@update')->name('updateVideo');
Route::delete('video/{video}', 'VideoController@destroy')->name('destroyVideo');

/*RESOURCES ROUTES FOR COMMENTS*/
Route::post('comment/{video}', 'CommentController@store')->name('storeComment');

/*RESOURCES ROUTES FOR USERS*/
Route::get('user/{user}', 'UserController@show')->name('userById');
Route::post('user/register', 'UserController@store')->name('registerUser');
Route::post('user/login', 'UserController@authenticate')->name('authenticateUser');
Route::post('user/logout', 'UserController@logout')->name('logoutUser');
Route::patch('user/{user}', 'UserController@update')->name('updateUser');
Route::delete('user/{user}', 'UserController@destroy')->name('destroyUser');
