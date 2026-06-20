<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AskController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomInstructionController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('/custom-instructions', [CustomInstructionController::class, 'edit'])->name('custom-instructions.edit');
    Route::put('/custom-instructions', [CustomInstructionController::class, 'update'])->name('custom-instructions.update');
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'ask'])->name('ask.post');

    Route::get('/chat/{conversation?}', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::post('/chat/stream', [ChatController::class, 'stream'])->name('chat.stream');
});

require __DIR__.'/settings.php';