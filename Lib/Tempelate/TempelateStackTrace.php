<?php
namespace Lib\Tempelate;

class TempelateStackTrace{
  private $stack = [];
  
  public function addFile(string $file){
    $this->stack[] = $file;
  }
  
  public function toArray() : array{
    return $this->stack;
  }
}