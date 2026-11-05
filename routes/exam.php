<?php
Route::middleware(['auth'])->group(function () {
    Route::livewire('/exam/quiz/create', 'pages::exam.quiz.create')->name('exam.create.quiz');
    Route::livewire('/exam/attempts/{attempt}', 'pages::exam.attempt.show')->name('exam.attempts.show');
});
