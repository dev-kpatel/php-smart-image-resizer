<?php

declare(strict_types=1);

use Common\Settings\Settings;
use Common\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => $_ENV['APP_ENV'] == 'local' ? true : false, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => 'php://stdout',
                    'level' => Logger::DEBUG,
                ],
                "config" => [
                    "BASE_IMAGE_DIR"  => $_ENV['BASE_IMAGE_DIR']  ?? (__DIR__ . '/../images'),
                    "RESIZED_DIR"     => $_ENV['RESIZED_DIR']     ?? (__DIR__ . './../public/resized'),
                    "DEFAULT_ENGINE"  => $_ENV['DEFAULT_ENGINE']  ?? 'gd',
                    "DEFAULT_QUALITY" => (int)($_ENV['DEFAULT_QUALITY'] ?? 82),
                    "DEFAULT_FIT"     => $_ENV['DEFAULT_FIT']     ?? 'contain',
                    "ADMIN_TOKEN"     => $_ENV['ADMIN_TOKEN']     ?? '',
                    "DIR_UPLOAD" => $_SERVER['DOCUMENT_ROOT']."/v2/courses/upload/",
                    "DIR_EMAIL" => $_SERVER['DOCUMENT_ROOT']."/v2/courses/emailtemplate/"
                ],
            ]);
        }
    ]);
};