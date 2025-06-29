<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

// PDF-related routes
Route::get('/projects/{project}/download-pdf', [ProjectController::class, 'downloadPDF'])->name('projects.download-pdf');
Route::get('/projects/{project}/preview-pdf', [ProjectController::class, 'previewPDF'])->name('projects.preview-pdf');
Route::post('/projects/{project}/regenerate-pdf', [ProjectController::class, 'regeneratePDF'])->name('projects.regenerate-pdf');