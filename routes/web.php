<?php
use Illuminate\Support\Facades\Route;

Route::any('{any}', function () {

})->where('any', '.*');
