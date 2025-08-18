<?php

declare(strict_types=1);

namespace Common\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CacheMiddleware implements Middleware
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $cache = new FilesystemAdapter();
        $uri = $request->getUri();
        $path = $uri->getPath();
        $cachekey = str_replace(['/v2/page/content/','/'], ['','-'], $path);
        // $cache->deleteItem($cachekey);
        $page = $cache->getItem($cachekey);
        if ($page->isHit()) {
            $response = $this->responseFactory->createResponse();
            $payload = json_encode([
                "status" => "ok",
                "data" =>  $page->get()
            ]);
            $response->getBody()->write($payload);
            $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Cache-Header', 'cached')
                ->withStatus(200);
            // $response->withJson($page->get())->withHeader('X-Cache-Header', 'cached');
            return $response;
        }
        $request = $request->withAttribute('cachekey', $cachekey);
        return $handler->handle($request);
    }
}
