<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TicketController::class, 'index'])->name('home');
Route::post('/tickets/{ticket}/duplicates', [TicketController::class, 'findDuplicates'])
    ->name('tickets.duplicates');
