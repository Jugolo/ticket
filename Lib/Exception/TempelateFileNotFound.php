<?php
namespace Lib\Exception;

class TempelateFileNotFound extends TempelateException{
  public function __construct(string $message){
    parent::__construct($message, "<unknown>", 0);
  }
}