<?php
namespace App\Services;

use App\Engines\ImageEngine;
use App\Engines\GDNativeEngine;
use App\Engines\InterventionEngine;

final class ImageService {
  public function __construct(
    private string $baseDir,
    private string $resizedDir,
    private string $defaultEngine = 'gd'
  ) {}

  public function pickEngine(?string $engine): ImageEngine {
    return match($engine ?? $this->defaultEngine) {
      'lib' => new InterventionEngine(),
      default => new GDNativeEngine(),
    };
  }

  public function cacheKey(string $relPath, array $opts): string {
    $norm = strtolower(trim($relPath));
    $hash = sha1($norm.'|'.json_encode($opts));
    $ext  = ($opts['fmt'] ?? 'jpg');
    return $hash.'.'.$ext;
  }

  public function cachedPath(string $key): string {
    return $this->resizedDir . '/' . $key;
  }
}
