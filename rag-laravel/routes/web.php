<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RagChatController;

Route::get('/rag-chat', [RagChatController::class, 'index'])
    ->name('rag.chat');

Route::post('/rag-chat', [RagChatController::class, 'chat'])
    ->name('rag.chat.send');



    