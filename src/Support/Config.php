<?php
namespace App\Support;

final class Config {
  public function __construct(
    public array $data
  ) {}
  public function __get($k) { return $this->data[$k] ?? null; }

  public function baseImageDir(): string { return rtrim($this->data['BASE_IMAGE_DIR'], '/'); }
  public function resizedDir(): string { return rtrim($this->data['RESIZED_DIR'], '/'); }
  public string $resizedDir;
  public string $baseImageDir;
  public string $defaultEngine;
  public int $defaultQuality;
  public string $defaultFit;

  public function __get_magic_initialization() {
    $this->resizedDir = $this->resizedDir();
    $this->baseImageDir = $this->baseImageDir();
    $this->defaultEngine = $this->data['DEFAULT_ENGINE'];
    $this->defaultQuality = (int)$this->data['DEFAULT_QUALITY'];
    $this->defaultFit = $this->data['DEFAULT_FIT'];
  }
}
