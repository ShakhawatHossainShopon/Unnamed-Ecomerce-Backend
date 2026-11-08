<?php

use App\Controllers\UserController;

/**
 * User API Routes
 * 
 * @var \App\Core\Router $router
 */

$router->get('/api/users', [UserController::class, 'index']);
$router->post('/api/users', [UserController::class, 'store']);
$router->get('/api/users/{id}', [UserController::class, 'show']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->patch('/api/users/{id}', [UserController::class, 'update']);
$router->post('/api/users/{id}/avatar', [UserController::class, 'uploadAvatar']);
$router->delete('/api/users/{id}', [UserController::class, 'destroy']);
