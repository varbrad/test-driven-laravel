<?php

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

Route::get('/concerts/{concert}', 'ConcertsController@show');

// API-ish

Route::post('/concerts/{concert}/orders', 'ConcertOrdersController@store');
