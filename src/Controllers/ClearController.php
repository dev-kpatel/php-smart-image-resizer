<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ClearController {
  public function __construct(private $config) {}

  public function clear(Request $req, Response $res): Response {
    $token = $req->getHeaderLine('X-Admin-Token'); // keep the header name (can change to X-Clear-Token later)
    if (!$token || $token !== ($this->config->data['ADMIN_TOKEN'] ?? '')) {
      $res->getBody()->write(json_encode(['error'=>'unauthorized']));
      return $res->withStatus(401)->withHeader('Content-Type','application/json');
    }

    $payload = (array)$req->getParsedBody();
    $pattern = trim((string)($payload['pattern'] ?? ''));
    $dryRun  = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $dir = rtrim($this->config->data['RESIZED_DIR'], '/');
    if (!is_dir($dir)) {
      $res->getBody()->write(json_encode(['status'=>'ok','deleted'=>0,'message'=>'no cache dir']));
      return $res->withHeader('Content-Type','application/json');
    }

    $deleted = 0; $scanned = 0;
    foreach (glob($dir.'/*') as $file) {
      if (!is_file($file)) continue;
      $scanned++;
      if ($pattern && !str_contains($file, $pattern) && !str_contains($file, sha1(strtolower($pattern)))) continue;
      if (!$dryRun) @unlink($file);
      $deleted++;
    }

    $res->getBody()->write(json_encode([
      'status'=>'ok','scanned'=>$scanned,'deleted'=>$deleted,'dry_run'=>$dryRun,'pattern'=>$pattern ?: null
    ]));
    return $res->withHeader('Content-Type','application/json');
  }
}
