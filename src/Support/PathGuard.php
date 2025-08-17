<?php
namespace App\Support;

final class PathGuard {
  public static function resolve(string $baseDir, string $relative): string {
    $clean = str_replace(['..', '\\'], ['', '/'], $relative);
    $full = realpath($baseDir . '/' . ltrim($clean, '/'));
    if ($full === false || !str_starts_with($full, realpath($baseDir))) {
      throw new \RuntimeException('Invalid path');
    }
    return $full;
  }
}
