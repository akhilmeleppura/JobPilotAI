<?php

use App\Modules\Outlook\app\Http\Controllers\OutlookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('outlook')->name('outlook.')->group(function () {
    // Primary Hub View
    Route::get('/', [OutlookController::class, 'index'])->name('index');

    // Mail Actions
    Route::post('/mail/send', [OutlookController::class, 'sendMessage'])->name('mail.send');
    Route::post('/mail/test-send', [OutlookController::class, 'sendLiveTestEmail'])->name('mail.test-send');
    Route::post('/mail/{id}/flag', [OutlookController::class, 'toggleFlag'])->name('mail.toggle-flag');
    Route::post('/mail/{id}/read', [OutlookController::class, 'toggleRead'])->name('mail.toggle-read');
    Route::delete('/mail/{id}', [OutlookController::class, 'deleteMessage'])->name('mail.delete');
    Route::get('/mail/{id}/ai-reply', [OutlookController::class, 'generateAIReply'])->name('mail.ai-reply');

    // Calendar Actions
    Route::post('/calendar/events', [OutlookController::class, 'storeEvent'])->name('calendar.store');
    Route::delete('/calendar/events/{id}', [OutlookController::class, 'deleteEvent'])->name('calendar.delete');

    // People / Contacts Actions
    Route::post('/people/contacts', [OutlookController::class, 'storeContact'])->name('people.store');
    Route::delete('/people/contacts/{id}', [OutlookController::class, 'deleteContact'])->name('people.delete');

    // To-Do Actions
    Route::post('/todo/tasks', [OutlookController::class, 'storeTask'])->name('todo.store');
    Route::post('/todo/tasks/{id}/toggle', [OutlookController::class, 'toggleTask'])->name('todo.toggle');
    Route::post('/todo/tasks/{id}/important', [OutlookController::class, 'toggleTaskImportant'])->name('todo.important');
    Route::delete('/todo/tasks/{id}', [OutlookController::class, 'deleteTask'])->name('todo.delete');
});
