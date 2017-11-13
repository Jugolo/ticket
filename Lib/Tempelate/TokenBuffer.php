<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;

class TokenBuffer{
  private $type;
  private $context;
  
  public function __construct(string $type, string $context){
    $this->type    = $type;
    $this->context = $context;
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
  
  public function expect(string $type){
    if($this->type != $type)
      throw new TempelateException("Unexpected {$this->type}({$this->context})");
  }
}