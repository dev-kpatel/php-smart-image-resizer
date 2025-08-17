<?php
namespace App\Support;

final class Srcset {
  /**
   * Build src/srcset/sizes for the /resize endpoint.
   *
   * @param string $relativePath e.g. "products/shirt.jpg"
   * @param int[]  $widths       e.g. [320, 480, 640, 960, 1200, 1600]
   * @param array  $params       e.g. ['fit' => 'cover', 'fmt' => 'webp', 'q' => 82, 'engine' => 'gd']
   * @param string|null $baseUrl absolute base like "https://media.example.com" (omit to use relative)
   * @return array{src:string, srcset:string, sizes:string}
   */
  public static function generate(string $relativePath, array $widths, array $params = [], ?string $baseUrl = null): array {
    $widths = array_values(array_unique(array_filter($widths, fn($w) => is_int($w) && $w > 0)));
    sort($widths);
    if (empty($widths)) $widths = [320, 640, 960, 1200];
    $qsBase = $params; unset($qsBase['w']); // width varies per candidate

    $buildUrl = function(int $w) use ($relativePath, $qsBase, $baseUrl) {
      $qs = array_merge($qsBase, ['w' => $w]);
      $q  = http_build_query($qs);
      $u  = '/resize/' . ltrim($relativePath, '/');
      $url = $u . ($q ? ('?' . $q) : '');
      return $baseUrl ? rtrim($baseUrl, '/') . $url : $url;
    };

    $srcsetParts = array_map(fn($w) => $buildUrl($w) . ' ' . $w . 'w', $widths);
    $largest = end($widths);
    $src = $buildUrl($largest);
    $srcset = implode(', ', $srcsetParts);
    $sizes = $params['sizes'] ?? '100vw';

    return ['src' => $src, 'srcset' => $srcset, 'sizes' => $sizes];
  }
}
