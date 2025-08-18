<?php
declare(strict_types=1);

namespace App\Actions\Image;

use Psr\Http\Message\ResponseInterface as Response;
use App\Support\PathGuard;
use App\Services\ImageService;

final class ResizeAction extends ImageAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response
  {
    $rel = isset($this->args['path']) ? $this->args['path'] : '';
    $q = $this->request->getQueryParams();
    $engine = $q['engine'] ?? $this->config->defaultEngine();

    try {
      $opts = [
        'w'   => isset($q['w']) ? (int)$q['w'] : null,
        'h'   => isset($q['h']) ? (int)$q['h'] : null,
        'fit' => $q['fit'] ?? $this->config->defaultFit(),
        'fmt' => $q['fmt'] ?? $this->negotiateFormat($q),
        'q'   => isset($q['q']) ? (int)$q['q'] : $this->config->defaultQuality(),
      ];

      $svc = new ImageService(
        $this->config->baseImageDir(),
        $this->config->resizedDir(),
        $engine
      );
      $key = $svc->cacheKey($rel, $opts);
      $cached = $svc->cachedPath($key);

      if (is_file($cached)) {
        return $this->sendFile($cached, $opts['fmt']);
      }

      $src = PathGuard::resolve($this->config->baseImageDir(), $rel);
      $engineImpl = $svc->pickEngine($engine);
      $result = $engineImpl->resize($src, $opts);

      file_put_contents($cached, $result['data']);
      return $this->sendFile($cached, $opts['fmt']);
      // return $this->respondWithData($return);
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   * return column name as per page type
   */
  private function sendFile(string $path, string $fmt): Response
  {
    $mime = $fmt === 'webp' ? 'image/webp' : 'image/jpeg';
    $etag = '"' . md5_file($path) . '"';

    // Optional: Nginx X-Accel-Redirect if configured
    // $docRoot = realpath($this->config->resizedDir());
    // if ($docRoot && str_starts_with(realpath($path), $docRoot)) {
    //   $internalPath = '/resized/' . basename($path);
    //   return $res->withHeader('X-Accel-Redirect', $internalPath);
    // }

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($ifNoneMatch === $etag) {
      return $this->response->withStatus(304);
    }
    $this->response->getBody()->write(file_get_contents($path));
    return $this->response
      ->withHeader('Content-Type', $mime)
      ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
      ->withHeader('ETag', $etag);
  }

  private function negotiateFormat(array $q): string
  {
    if (!empty($q['fmt'])) {
      return $q['fmt'];
    }
    $accept = $this->request->getHeader('Accept')[0];
    if (stripos($accept, 'image/webp') !== false) {
      return 'webp';
    }
    return 'jpg';
  }
}
