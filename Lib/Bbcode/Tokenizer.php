<?php
namespace Lib\Bbcode;

class Tokenizer{
  private $length;
  private $string;
  private $pos = 0;
  
  public function __construct(string $str){
    $this->string = $str;
    $this->length = strlen($this->string);
  }
  
  public function eos(){
    return $this->length-1 < $this->pos; 
  }
  
  public function next(){
     $pos = strcspn($this->string, "[]", $this->pos);
     if($pos == 0){
       $this->pos++;
       return $this->string[$this->pos-1];
     }
     $str = substr($this->string, $this->pos, $pos);
     $this->pos += $pos;
     return $str;
  }
}