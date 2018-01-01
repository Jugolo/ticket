<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;
use Lib\Tempelate\TempelateFileReader;

class TokenBuffer{
  private $type;
  private $context;
  private $reader;
  
  public function __construct(string $type, string $context, TempelateFileReader $reader){
    $this->type    = $type;
    $this->context = $context;
    $this->reader  = $reader;
  }
  
  public function getType(){
    return $this->type;
  }
  
  public function getContext(){
    return $this->context;
  }
  
  public function isKeyword() : bool{
    return $this->type == "KEYWORD";
  }
  
  public function expect(string $type, string $value = ""){
    if($this->type != $type || $value && $this->context != $value)
      throw new TempelateException("Unexpected {$this->type}({$this->context})", $this->reader->getFile(), $this->reader->getLine());
  }
  
  public function test(string $type, string $value = ""){
    if($value)
      return $this->type == $type && $this->context == $value;
    return $this->type == $type;
  }
}