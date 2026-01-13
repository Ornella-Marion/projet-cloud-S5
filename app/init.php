<?php
// Basic autoload (simple)
spl_autoload_register(function ($class) {
    $paths = [__DIR__ . '/controllers/', __DIR__ . '/core/'];
    foreach ($paths as $p) {
        $file = $p . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Load config
$config = require __DIR__ . '/../../config/config.php';

// Create router
$router = new Router();

// Routes
$router->add('POST', '/api/signup', [new AuthController(), 'signup']);
$router->add('POST', '/api/login', [new AuthController(), 'login']);
$router->add('PUT', '/api/users/{id}', [new AuthController(), 'update']);
