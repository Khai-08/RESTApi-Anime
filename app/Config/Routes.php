<?php

use CodeIgniter\Router\RouteCollection;

use App\Controllers\AuthenticationController;
use App\Controllers\AnimeController;

$routes->get('/', 'Home::index');

// Authentication Routes
$routes->group('api/auth', function ($routes) {
    $routes->post('forgotPassword', [AuthenticationController::class, 'forgotPassword']);
    $routes->post('resetPassword', [AuthenticationController::class, 'resetPassword']);
    $routes->post('resendEmail', [AuthenticationController::class, 'resendEmail']);
    $routes->post('register', [AuthenticationController::class, 'register']);
    $routes->post('login', [AuthenticationController::class, 'login']);
    $routes->post('logout', [AuthenticationController::class, 'logout']);
    $routes->get('verify', [AuthenticationController::class, 'verify']);    
});


$routes->group('api/', function ($routes) {
    $routes->post('addAnime', [AnimeController::class, 'create']);
    $routes->post('editAnime', [AnimeController::class, 'edit']);
});