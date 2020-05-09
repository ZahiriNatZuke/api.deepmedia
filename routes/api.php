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
Route::get('video/stats/{video}', 'VideoController@stats')->name('statsByVideo');
Route::get('video/by/{category}', 'VideoController@index')->name('videoByCategory');
Route::post('video', 'VideoController@store')->name('storeVideo')
    ->middleware(['jwt_auth']);
Route::patch('video/{video}', 'VideoController@update')->name('updateVideo')
    ->middleware(['jwt_auth']);
Route::delete('video/{video}', 'VideoController@destroy')->name('destroyVideo')
    ->middleware(['jwt_auth']);

/*RESOURCES ROUTES FOR COMMENTS*/
Route::post('comment/{video}', 'CommentController@store')->name('storeComment')
    ->middleware(['jwt_auth']);

/*RESOURCES ROUTES FOR USERS*/
Route::get('user/{user}', 'UserController@show')->name('userById');
Route::post('user/register', 'UserController@store')->name('registerUser');
Route::post('user/login', 'UserController@authenticate')->name('authenticateUser');
Route::post('user/logout', 'UserController@logout')->name('logoutUser');
Route::patch('user/{user}', 'UserController@update')->name('updateUser')
    ->middleware(['jwt_auth']);
Route::delete('user/{user}', 'UserController@destroy')->name('destroyUser')
    ->middleware(['jwt_auth']);

/*RESOURCES ROUTES FOR CHANNELS*/
Route::get('channel/{channel}', 'ChannelController@show')->name('channelById');
Route::get('channel/stats/{channel}', 'ChannelController@stats')->name('statsByChannel');
