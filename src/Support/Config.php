<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Strongly-initialized config with safe getters.
 */
final class Config {
  private array $data;
  private string $baseImageDir;
  private string $resizedDir;
  private string $defaultEngine;
  private int $defaultQuality;
  private string $defaultFit;
  private string $adminToken;

  public function __construct(array $data) {
    $this->data = $data;
    $this->baseImageDir   = rtrim($data['BASE_IMAGE_DIR'] ?? (__DIR__ . '/../../images'), '/');
    $this->resizedDir     = rtrim($data['RESIZED_DIR']     ?? (__DIR__ . '/../../public/resized'), '/');
    $this->defaultEngine  = $data['DEFAULT_ENGINE']  ?? 'gd';
    $this->defaultQuality = (int)($data['DEFAULT_QUALITY'] ?? 82);
    $this->defaultFit     = $data['DEFAULT_FIT']     ?? 'contain';
    $this->adminToken     = $data['ADMIN_TOKEN']     ?? '';
  }

  /** Raw access if you really need it */
  public function get(string $key, mixed $default = null): mixed {
    return $this->data[$key] ?? $default;
  }

  public function baseImageDir(): string { return $this->baseImageDir; }
  public function resizedDir(): string { return $this->resizedDir; }
  public function defaultEngine(): string { return $this->defaultEngine; }
  public function defaultQuality(): int { return $this->defaultQuality; }
  public function defaultFit(): string { return $this->defaultFit; }
  public function adminToken(): string { return $this->adminToken; }
}
