<?php

Route::middleware(['auth'])->group(function () {
    Route::livewire('/exam/quiz/create', 'pages::exam.quiz.create')->name('exam.create.quiz');
    Route::livewire('/exam/final-mid/create', 'pages::exam.final-mid.create')->name('exam.final-mid.create');
    Route::livewire('/exam/attempts/{attempt}', 'pages::exam.attempt.show')->name('exam.attempts.show');
    Route::livewire('/exam/attempts/{attempt}/result', 'pages::exam.result.show')->name('exam.results.show');
    Route::livewire('/exam', 'pages::exam.index')->name('exam.index');
    Route::livewire('/exam/mock/create', 'pages::exam.mock.create')->name('exam.mock.create');

});
