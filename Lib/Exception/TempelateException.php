<?php
namespace Lib\Exception;

class TempelateException extends \Exception{
  public function __construct(string $message, string $file, int $line){
    $this->message = $message;
    $this->file    = $file;
    $this->line    = $line;
  }
}