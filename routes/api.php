<?php

use App\Http\Controllers\Api\PortalApiController;
use Illuminate\Support\Facades\Route;

Route::get('/home', [PortalApiController::class, 'home']);
Route::get('/countries', [PortalApiController::class, 'countries']);
Route::get('/channels', [PortalApiController::class, 'channels']);
Route::get('/hotel', [PortalApiController::class, 'hotel']);
Route::get('/contacts', [PortalApiController::class, 'hotel']);
Route::get('/menu', [PortalApiController::class, 'hotel']);
