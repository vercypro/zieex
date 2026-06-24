<?php

use Zieex\Router\Router;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\UserApiController;

Router::group(['prefix' => '/api'], function () {

    // Auth - public
    Router::post('/auth/login',    [AuthApiController::class, 'login'])->rateLimit(5, 60);
    Router::post('/auth/register', [AuthApiController::class, 'register'])->rateLimit(3, 60);

    // Protected API routes
    Router::group(['middleware' => ['jwt']], function () {
        Router::get('/auth/me',    [AuthApiController::class, 'me']);
        Router::post('/auth/logout', [AuthApiController::class, 'logout']);

        // Resource routes
        Router::resource('/users', UserApiController::class);
    });
});
