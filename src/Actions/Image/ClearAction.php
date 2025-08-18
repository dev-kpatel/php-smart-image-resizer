<?php
declare(strict_types=1);

namespace App\Actions\Image;

use Psr\Http\Message\ResponseInterface as Response;

final class ClearAction extends ImageAction
{

   /**
   * {@inheritdoc}
   */
  protected function action(): Response
  {
    $token = $this->request->getHeader('X-Admin-Token')[0];

    if (!$token || $token !== $this->config->adminToken()) {
      return $this->respondWithData(['error'=>'unauthorized'])->withStatus(401);
    }

    $payload = $this->getFormData();
    $pattern = trim((string)($payload['pattern'] ?? ''));
    $dryRun  = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $dir = rtrim($this->config->resizedDir(), '/');
    if (!is_dir($dir)) {
        return $this->respondWithData(['status'=>'ok','deleted'=>0,'message'=>'no cache dir']);
    }

    $deleted = 0; $scanned = 0;
    foreach (glob($dir.'/*') as $file) {
      if (!is_file($file)) continue;
      $scanned++;
      // match raw filename or sha1 used in cache key
      if ($pattern && !str_contains($file, $pattern) && !str_contains($file, sha1(strtolower($pattern)))) continue;
      if (!$dryRun) @unlink($file);
      $deleted++;
    }

    return $this->respondWithData([
      'status'=>'ok','scanned'=>$scanned,'deleted'=>$deleted,'dry_run'=>$dryRun,'pattern'=>$pattern ?: null
    ]);
  }
}
