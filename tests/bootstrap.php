<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load env from config/.env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->safeLoad();

// Build container
$containerBuilder = new ContainerBuilder();
($settings = require __DIR__ . '/../app/settings.php')($containerBuilder);
($dependencies = require __DIR__ . '/../app/dependencies.php')($containerBuilder);
$container = $containerBuilder->build();

// Create Slim app with container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register routes ONCE (guard)
if (empty($GLOBALS['__routes_registered'])) {
    ($routes = require __DIR__ . '/../app/routes.php')($app);
    $GLOBALS['__routes_registered'] = true;
}

// Expose app (single instance) to tests
$GLOBALS['app'] = $app;
