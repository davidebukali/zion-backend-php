<?php

use Illuminate\Support\Facades\Route;
use Modules\Messages\Http\Controllers\ConversationController;
use Modules\Messages\Http\Controllers\MessageController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Conversations
    Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('conversations/recipients', [ConversationController::class, 'recipients'])->name('conversations.recipients');
    Route::post('conversations', [ConversationController::class, 'store'])->middleware('throttle:conversations.create')->name('conversations.store');
    Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

    Route::post('conversations/{conversation}/read', [ConversationController::class, 'read'])->name('conversations.read');
    Route::post('conversations/{conversation}/typing', [ConversationController::class, 'typing'])->middleware('throttle:messages.typing')->name('conversations.typing');
    Route::post('conversations/{conversation}/archive', [ConversationController::class, 'archive'])->name('conversations.archive');
    Route::post('conversations/{conversation}/unarchive', [ConversationController::class, 'unarchive'])->name('conversations.unarchive');
    Route::post('conversations/{conversation}/mute', [ConversationController::class, 'mute'])->name('conversations.mute');
    Route::post('conversations/{conversation}/unmute', [ConversationController::class, 'unmute'])->name('conversations.unmute');
    Route::post('conversations/{conversation}/report', [ConversationController::class, 'report'])->name('conversations.report');

    // Messages within Conversation
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])->name('conversations.messages.index');
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:messages.send')->name('conversations.messages.store');

    // Message Actions
    Route::patch('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('messages/{message}/delete-for-me', [MessageController::class, 'deleteForMe'])->name('messages.delete-for-me');
    Route::post('messages/{message}/reactions', [MessageController::class, 'react'])->name('messages.reactions.store');
    Route::delete('messages/{message}/reactions', [MessageController::class, 'removeReaction'])->name('messages.reactions.destroy');
    Route::post('messages/{message}/report', [MessageController::class, 'report'])->name('messages.report');
});
