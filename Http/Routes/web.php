<?php

use Illuminate\Support\Facades\Route;
use Modules\DistanceCal\Http\Controllers\Frontend\IndexController;

Route::group(['middleware' => ['web','auth']], function () {
    // Form page
    Route::get('/', [IndexController::class, 'index'])->name('distancecal.index');
    
    // AJAX endpoint for distance calculation
    Route::get('/calculate', [IndexController::class, 'calculate'])->name('calculate');
});




