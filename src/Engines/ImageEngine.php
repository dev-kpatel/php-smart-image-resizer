<?php
namespace App\Engines;

interface ImageEngine {
  /**
   * @param array{w?:int,h?:int,fit?:string,fmt?:string,q?:int,progressive?:bool} $opts
   * @return array{data:string,mime:string,ext:string}
   */
  public function resize(string $sourcePath, array $opts): array;
}
