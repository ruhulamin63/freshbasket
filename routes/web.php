<?php

use Illuminate\Support\Facades\Route;

Route::middleware('locale')->get('/', fn () => view('catalog'))->name('catalog');
