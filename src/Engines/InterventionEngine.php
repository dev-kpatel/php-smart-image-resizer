<?php
namespace App\Engines;

use Intervention\Image\ImageManager;

final class InterventionEngine implements ImageEngine {
  private ImageManager $manager;
  public function __construct() {
    $this->manager = ImageManager::withDriver('gd'); // or 'imagick' if available
  }

  public function resize(string $sourcePath, array $opts): array {
    $img = $this->manager->read($sourcePath)->orientate();
    $fit = $opts['fit'] ?? 'contain';
    $fmt = $opts['fmt'] ?? 'jpg';
    $q = (int)($opts['q'] ?? 82);
    $w = (int)($opts['w'] ?? 0); $h = (int)($opts['h'] ?? 0);

    if (!$w && !$h) { $w = $img->width(); $h = $img->height(); }

    if ($fit === 'cover') $img->coverDown($w, $h);
    else $img->scaleDown($w, $h);

    // Progressive JPEG
    if ($fmt === 'webp') {
      $ext='webp'; $mime='image/webp';
      $img->toWebp($q);
    } else {
      $ext='jpg'; $mime='image/jpeg';
      $img->withProgressive()->toJpeg($q);
    }

    return ['data'=>(string)$img, 'mime'=>$mime, 'ext'=>$ext];
  }
}
