<?php
declare(strict_types=1);

namespace Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Reuses the single app from tests/bootstrap.php.
 * Does NOT create or register routes again.
 */
final class TestRequest
{
    private static function app(): App
    {
        $app = $GLOBALS['app'] ?? null;
        if (!$app instanceof App) {
            throw new \RuntimeException('Slim app not found. Ensure phpunit.xml.dist bootstraps tests/bootstrap.php.');
        }
        return $app;
    }

    public static function get(string $path, array $headers = []): ServerRequestInterface
    {
        $uri = (new UriFactory())->createUri($path);
        $req = (new ServerRequestFactory())->createServerRequest('GET', $uri);
        foreach ($headers as $k => $v) $req = $req->withHeader($k, (string)$v);
        return $req;
    }

    public static function postJson(string $path, array $json, array $headers = []): ServerRequestInterface
    {
        $uri = (new UriFactory())->createUri($path);
        $stream = (new StreamFactory())->createStream(json_encode($json));
        $req = (new ServerRequestFactory())->createServerRequest('POST', $uri)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
        foreach ($headers as $k => $v) $req = $req->withHeader($k, (string)$v);
        return $req;
    }

    /**
     * Convenience: build a request and return the handled response.
     * Keeps older tests that call ::request(...) working.
     */
    public static function request(string $method, string $path, array $opts = []): ResponseInterface
    {
        $headers = (array)($opts['headers'] ?? []);
        $uri = (new UriFactory())->createUri($path);
        $req = (new ServerRequestFactory())->createServerRequest(strtoupper($method), $uri);

        if (isset($opts['json'])) {
            $stream = (new StreamFactory())->createStream(json_encode($opts['json']));
            $req = $req->withHeader('Content-Type', 'application/json')->withBody($stream);
        } elseif (isset($opts['body'])) {
            $stream = (new StreamFactory())->createStream((string)$opts['body']);
            $req = $req->withBody($stream);
        }
        foreach ($headers as $k => $v) $req = $req->withHeader($k, (string)$v);

        return self::app()->handle($req);
    }
}
