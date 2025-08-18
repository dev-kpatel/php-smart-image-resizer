<?php

declare(strict_types=1);

use Common\Middleware\SessionMiddleware;
use RKA\Middleware\IpAddress;
use JimTools\JwtAuth\Middleware\JwtAuthentication;
use Slim\App;

return function (App $app) {
    $app->add(SessionMiddleware::class);
    $app->add(RKA\Middleware\IpAddress::class);
    $app->add(JwtAuthentication::class);
};
