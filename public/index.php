<?php
declare(strict_types=1);

use Common\Handlers\HttpErrorHandler;
use Common\Handlers\ShutdownHandler;
use Common\ResponseEmitter\ResponseEmitter;
use Common\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Slim\Exception\NotFoundException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Load .env from project root (copy config/app.example.env -> .env first).
 */
$dotenv = Dotenv::createImmutable(dirname(__DIR__) . '/config');
$dotenv->safeLoad();

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

$projectRoot = dirname(__DIR__); // one level up from /public
if (($_ENV['APP_ENV'] ?? 'local') !== 'local') {
    $containerBuilder->enableCompilation($projectRoot . '/var/cache/container');
    // optional: write proxies for opcache-friendly class loading
    $containerBuilder->writeProxiesToFile(true, $projectRoot . '/var/cache/proxies');
}

// Set up settings
$settings = require __DIR__ . './../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . './../app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();
//set the upload path
// $container->set('upload_directory', __DIR__ . '/upload');
// Instantiate the app

AppFactory::setContainer($container);
$app = AppFactory::create();
// Set Slim base path
// $app->setBasePath($_ENV['BASEPATH']);

$callableResolver = $app->getCallableResolver();

// Register middleware
// $middleware = require __DIR__ . '/app/middleware.php';
// $middleware($app);

// Register routes
$routes = require __DIR__ . './../app/routes.php';
$routes($app);

/** @var SettingsInterface $settings */
$settings = $container->get(SettingsInterface::class);


$displayErrorDetails = $settings->get('displayErrorDetails');
$logError = $settings->get('logError');
$logErrorDetails = $settings->get('logErrorDetails');

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);