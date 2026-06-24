<?php

use Zieex\Router\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;

// Public routes
Router::get('/', [HomeController::class, 'index'])->name('home');

// Auth routes
Router::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Router::post('/login',   [AuthController::class, 'login'])->rateLimit(5, 60);
Router::get('/register', [AuthController::class, 'showRegister'])->name('register');
Router::post('/register',[AuthController::class, 'register']);
Router::post('/logout',  [AuthController::class, 'logout'])->middleware('auth');

// Protected routes
Router::group(['prefix' => '/dashboard', 'middleware' => ['auth']], function () {
    Router::get('', [HomeController::class, 'dashboard'])->name('dashboard');
});
