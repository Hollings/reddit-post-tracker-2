<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/post','App\Http\Controllers\PostWatcherController@index');
Route::get('/r/{all?}','App\Http\Controllers\PostWatcherController@index');
Route::get('/post/new','App\Http\Controllers\PostWatcherController@store');
Route::get('/post/update','App\Http\Controllers\PostWatcherController@update');
Route::get('/post/{postWatcher}','App\Http\Controllers\PostWatcherController@show');
Route::get('/byidtest/','App\Http\Controllers\PostWatcherController@byIdTest');
Route::get('/r/{subreddit}/comments/{reddit_id}/{extra_url}','App\Http\Controllers\PostWatcherController@redditurltest');
Route::get('rising','App\Http\Controllers\PostWatcherController@getRandomIdFromRising');
Route::get('frontpage', 'App\Http\Controllers\PostWatcherController@frontPage');
