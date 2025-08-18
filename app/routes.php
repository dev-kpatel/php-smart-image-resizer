<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

//Page Namespace
use App\Actions\Image\ResizeAction;
use App\Actions\Image\ClearAction;

return function (App $app) {

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

     $app->get('/hello', function (Request $request, Response $response) {
        $response->getBody()->write('Hello World');
        return $response;
    });

    $app->get('/resize/{path:.*}', ResizeAction::class); //Done and Tested
    $app->post('/cache/clear',  ClearAction::class); //Done and Tested
    $app->post('/cache/clear',  ClearAction::class); //Done and Tested
    $app->get('/healthz', function ($req, $res) {
    $res = $res->withHeader('Content-Type', 'application/json');
        $res->getBody()->write(json_encode(['ok' => true]));
        return $res;
    });

};