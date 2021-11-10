<?php

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
Route::post('/login', 'LoginController@login'); 

Route::middleware('fireauth')->group(function () {
    Route::get('/user', 'UserController@getdata'); 
    Route::post('/user', 'UserController@store');
    Route::put('/user', 'UserController@updateData');
    Route::delete('/user', 'UserController@destroy');
    Route::get('/users/moim', 'UserController@getUserMoim');
    Route::get('/user/{id}', 'UserController@index');
    
    Route::post('/moim', 'MoimController@store');
    Route::put('/moim/{moim_id}', 'MoimController@updateMoim');
    Route::patch('/moim/{moim_id}', 'MoimController@update');
    Route::get('/moim/{moim_id}', 'MoimController@getDetailMoim');
    Route::get('/moim/{moim_id}/post', 'PostController@getPostMoim');
    Route::get('/moim/{moim_id}/members', 'MoimController@getMembersMoim');
    
    Route::get('/invitation', 'InvitationController@getInvitation');
    Route::post('/invitation', 'InvitationController@Store');

    Route::post('/post', 'PostController@Store');
    Route::put('/post/{id}', 'PostController@update');
    Route::delete('/post/{id}', 'PostController@destroy');
    Route::get('/post/{id}', 'PostController@getdata');
    
    
    Route::get('/categories', 'CategoryController@index');
});

Route::post('/get', 'UserController@data');