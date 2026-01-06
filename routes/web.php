<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home route - redirects based on authentication status
Route::get('/', function () {
    // If user is authenticated, redirect to dashboard
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    
    // Otherwise, redirect to login page
    return redirect()->route('login');
})->name('home');

// Dashboard route - protected by auth middleware
// Only authenticated users can access this route
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

// Include authentication routes from Breeze
require __DIR__.'/auth.php';