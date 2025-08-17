<?php
namespace App\Engines;

final class GDNativeEngine implements ImageEngine {
  private function load(string $path) {
    $info = getimagesize($path);
    if (!$info) throw new \RuntimeException('Unsupported image');
    [$w, $h, $type] = $info;
    return match($type) {
      IMAGETYPE_JPEG => imagecreatefromjpeg($path),
      IMAGETYPE_PNG  => imagecreatefrompng($path),
      IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
      default => null
    } ?? throw new \RuntimeException('Unsupported format for GDNative');
  }

  public function resize(string $sourcePath, array $opts): array {
    $fit = $opts['fit'] ?? 'contain';
    $fmt = $opts['fmt'] ?? 'jpg';
    $quality = (int)($opts['q'] ?? 82);
    $targetW = (int)($opts['w'] ?? 0);
    $targetH = (int)($opts['h'] ?? 0);

    $src = $this->load($sourcePath);
    $sw = imagesx($src); $sh = imagesy($src);

    if (!$targetW && !$targetH) { $targetW = $sw; $targetH = $sh; }
    if (!$targetW) $targetW = (int)round($sw * ($targetH / $sh));
    if (!$targetH) $targetH = (int)round($sh * ($targetW / $sw));

    // compute cover/contain
    $srcX = $srcY = 0; $srcW = $sw; $srcH = $sh;
    if ($fit === 'cover') {
      $ratio = max($targetW / $sw, $targetH / $sh);
      $rw = (int)round($targetW / $ratio);
      $rh = (int)round($targetH / $ratio);
      $srcX = (int)max(0, ($sw - $rw) / 2);
      $srcY = (int)max(0, ($sh - $rh) / 2);
      $srcW = min($rw, $sw);
      $srcH = min($rh, $sh);
    } else if ($fit === 'contain' || $fit === 'scale') {
      $ratio = min($targetW / $sw, $targetH / $sh);
      $targetW = (int)round($sw * $ratio);
      $targetH = (int)round($sh * $ratio);
    }

    $dst = imagecreatetruecolor($targetW, $targetH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);

    ob_start();
    $mime='image/jpeg'; $ext='jpg';
    if ($fmt === 'webp' && function_exists('imagewebp')) {
      imagewebp($dst, null, $quality); $mime='image/webp'; $ext='webp';
    } else {
      // progressive JPEG
      imageinterlace($dst, true);
      imagejpeg($dst, null, $quality); $mime='image/jpeg'; $ext='jpg';
    }
    $data = ob_get_clean();

    imagedestroy($src); imagedestroy($dst);
    return ['data'=>$data, 'mime'=>$mime, 'ext'=>$ext];
  }
}
