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
    ->middleware(['jwt_auth', 'jwt_grant:video']);
Route::delete('video/{video}', 'VideoController@destroy')->name('destroyVideo')
    ->middleware(['jwt_auth', 'jwt_grant:destroy']);

/*RESOURCES ROUTES FOR COMMENTS*/
Route::get('comment/{video}', 'CommentController@index')->name('commentsByVideo');
Route::post('comment/{video}', 'CommentController@store')->name('storeComment')
    ->middleware(['jwt_auth']);

/*RESOURCES ROUTES FOR USERS*/
Route::get('user/{user}', 'UserController@show')->name('userById');
Route::post('user/register', 'UserController@store')->name('registerUser');
Route::post('user/login', 'UserController@authenticate')->name('authenticateUser');
Route::post('user/logout', 'UserController@logout')->name('logoutUser')
    ->middleware(['jwt_auth']);
Route::patch('user/{user}', 'UserController@update')->name('updateUser')
    ->middleware(['jwt_auth', 'jwt_grant:user']);
Route::delete('user/{user}', 'UserController@destroy')->name('destroyUser')
    ->middleware(['jwt_auth', 'jwt_grant:destroy']);
Route::post('user/jwt/refresh', 'UserController@refresh')->name('refreshJWT');

/*RESOURCES ROUTES FOR CHANNELS*/
Route::get('channel/{channel}', 'ChannelController@show')->name('channelById');
Route::get('channel/stats/{channel}', 'ChannelController@stats')->name('statsByChannel');

/*AUX ROUTES*/
Route::post('like/{video}', 'AuxController@like')->name('likeVideo')
    ->middleware('jwt_auth');
Route::post('favorite/{video}', 'AuxController@favorite')->name('favoriteVideo')
    ->middleware('jwt_auth');
Route::get('my_favorites', 'AuxController@favorite_user')->name('favoritesByUser')
    ->middleware('jwt_auth');
Route::get('count_video_by_categories', 'AuxController@countVideoByCategories')->name('countVideoByCategories');
Route::get('top_video', 'AuxController@topVideo')->name('topVideo');
Route::get('top_video/channel/{channel}', 'AuxController@topVideoByChannel')->name('topVideoByChannel');
