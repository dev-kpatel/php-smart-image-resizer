<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Support\Config;
use App\Support\PathGuard;
use App\Services\ImageService;

final class ResizeController {
  public function __construct(private Config $config) {}

  public function handle(Request $req, Response $res, array $args): Response {
    $q = $req->getQueryParams();
    $rel = $args['path'] ?? '';
    $engine = $q['engine'] ?? $this->config->data['DEFAULT_ENGINE'];

    $opts = [
      'w' => isset($q['w']) ? (int)$q['w'] : null,
      'h' => isset($q['h']) ? (int)$q['h'] : null,
      'fit' => $q['fit'] ?? $this->config->data['DEFAULT_FIT'],
      'fmt' => $q['fmt'] ?? $this->negotiateFormat($req, $q),
      'q'   => isset($q['q']) ? (int)$q['q'] : (int)$this->config->data['DEFAULT_QUALITY'],
    ];

    $svc = new ImageService($this->config->data['BASE_IMAGE_DIR'], $this->config->data['RESIZED_DIR'], $engine);
    $key = $svc->cacheKey($rel, $opts);
    $cached = $svc->cachedPath($key);

    if (is_file($cached)) {
      return $this->sendFile($res, $cached, $opts['fmt']);
    }

    $src = PathGuard::resolve($this->config->data['BASE_IMAGE_DIR'], $rel);
    $engineImpl = $svc->pickEngine($engine);
    $result = $engineImpl->resize($src, $opts);

    file_put_contents($cached, $result['data']);
    return $this->sendFile($res, $cached, $opts['fmt']);
  }

  private function sendFile(Response $res, string $path, string $fmt): Response {
    $mime = $fmt === 'webp' ? 'image/webp' : 'image/jpeg';
    $etag = '"' . md5_file($path) . '"';
    $res = $res
      ->withHeader('Content-Type', $mime)
      ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
      ->withHeader('ETag', $etag);

    // Conditional GET
    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($ifNoneMatch === $etag) {
      return $res->withStatus(304);
    }

    $res->getBody()->write(file_get_contents($path));
    return $res;
  }

  private function negotiateFormat(Request $req, array $q): string {
    if (!empty($q['fmt'])) return $q['fmt'];
    $accept = $req->getHeaderLine('Accept');
    if (stripos($accept, 'image/webp') !== false) return 'webp';
    return 'jpg';
  }
}
