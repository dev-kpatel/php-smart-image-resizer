<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use App\Support\Config;
use App\Controllers\ResizeController;
use App\Controllers\ClearController;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__) . '/config', 'app.example.env');
$dotenv->safeLoad();

$config = new Config([
  'BASE_IMAGE_DIR' => $_ENV['BASE_IMAGE_DIR'] ?? (__DIR__.'/../images'),
  'RESIZED_DIR'    => $_ENV['RESIZED_DIR']    ?? (__DIR__.'/resized'),
  'DEFAULT_ENGINE' => $_ENV['DEFAULT_ENGINE'] ?? 'gd',
  'DEFAULT_QUALITY'=> (int)($_ENV['DEFAULT_QUALITY'] ?? 82),
  'DEFAULT_FIT'    => $_ENV['DEFAULT_FIT'] ?? 'contain',
  'ADMIN_TOKEN'    => $_ENV['ADMIN_TOKEN'] ?? ''
]);

if (!is_dir($config->resizedDir)) { @mkdir($config->resizedDir, 0777, true); }

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// routes
$app->get('/resize/{path:.*}', [new ResizeController($config), 'handle']);
$app->post('/cache/clear', [new ClearController($config), 'clear']);
$app->get('/healthz', fn($req, $res) => $res->withHeader('Content-Type','application/json')->write(json_encode(['ok'=>true])));

$app->run();
