<?php

use App\Http\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['hotel' => config('hotel')]);
});

Route::get('/country', [CountryController::class, 'countries'])->name('channels');
Route::get('/show/{country_code}', [CountryController::class, 'showChannels'])->name('country.show');
Route::get('/player', [CountryController::class, 'player'])->name('player');
Route::get('/stream', [CountryController::class, 'proxy'])->name('stream.proxy');

Route::get('/contacts', function () {
    return view('contacts', ['hotel' => config('hotel')]);
});

Route::get('/hotel', function () {
    return view('hotel', ['hotel' => config('hotel')]);
});

Route::get('/menu', function () {
    return view('menu', ['hotel' => config('hotel')]);
});
