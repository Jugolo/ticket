<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;

class Tokenizer{
  private $index = 0;
  private $code;
  private $buffer;
  
  public function __construct(string $code){
    $this->code = $code;
  }
  
  public function current() : TokenBuffer{
    if(!$this->buffer)
      $this->next();
    return $this->buffer;
  }
  
  public function next() : TokenBuffer{
    return $this->buffer = $this->generate();
  }
  
  public function end(){
    return $this->index > strlen($this->code)-1;
  }
  
  private function generate() : TokenBuffer{
    $char = $this->toNextChar();
    if($char  === null){
      return new TokenBuffer("EOF", "End of file");
    }
    if($this->variabelStart($char))
      return $this->getVariabel($char);
    
    if($this->number($char))
      return $this->getNumber($char);
    
    if($char == "'" || $char == '"')
      return $this->getString($char);
    
    return $this->getPunctor($char);
  }
  
  private function getPunctor(string $code){
    switch($code){
      case ".":
        return new TokenBuffer("PUNCTOR", ".");
      case ":":
        return new TokenBuffer("PUNCTOR", ":");
      case "!":
        if($this->end() || $this->code[$this->index] != "=")
          throw new TempelateException("Unexpected char !");
        $this->index++;
        return new TokenBuffer("PUNCTOR", "!=");
      case "=":
        if(!$this->end() && $this->code[$this->index] == "="){
          $this->index++;
          return new TokenBuffer("PUNCTOR", "==");
        }
        return new TokenBuffer("PUNCTOR", "=");
      case "[":
        return new TokenBuffer("PUNCTOR", "[");
      case "]":
        return new TokenBuffer("PUNCTOR", "]");
    }
    throw new TempelateException("Unexpected char ".$code);
  }
  
  private function number(string $char){
    $c = ord($char);
    return $c >= 48 && $c <= 57;
  }
  
  private function getString(string $end){
    $str = "";
    while(true){
      if($this->end())
        throw new TempelateException("Missing end of string. Get end of file");
      $char = $this->code[$this->index];
      $this->index++;
      if($end == $char)
        break;
      $str .= $char;
    }
    return new TokenBuffer("STRING", $str);
  }
  
  private function getNumber(string $char){
    $number = $char.$this->getRawNumber();
    if(!$this->end() && $this->code[$this->index] == "."){
      $this->index++;
      $number .= ".".$this->getRawNumber();
    }
    return new TokenBuffer("NUMBER", $number);
  }
  
  private function getRawNumber(){
    $result = "";
    while(!$this->end() && $this->number($this->code[$this->index])){
      $result .= $this->code[$this->index];
      $this->index++;
    }
    return $result;
  }
  
  private function variabelStart(string $char) : bool{
    $c = ord($char);
    return $c >= 65 && $c <= 90 || $c >= 97 && $c <= 122;
  }
  
  private function variabel(string $char) : bool{
    return $this->variabelStart($char) || $this->number($char) || $char == "_";
  }
  
  private function getVariabel(string $identify){
    while(!$this->end()){
      if(!$this->variabel($this->code[$this->index]))
        break;
      $identify .= $this->code[$this->index];
      $this->index++;
    }
    
    if(in_array($identify, [
      "include",
      "addCss",
      "getStyle",
      "addScript",
      "getScript",
      "config",
      "if",
      "elseif",
      "else",
      "access",
      "endblock",
      "loggedIn",
      "foreach",
      "as",
      "echo",
      "set",
      "or",
      "and",
      "not"
      ]))
       return new TokenBuffer("KEYWORD", $identify);
    
    return new TokenBuffer("IDENTIFY", $identify);
  }
  
  private function toNextChar(){
    while(!$this->end()){
      $char = $this->code[$this->index];
      $this->index++;
      if($char == " "){
        continue;
      }
      return $char;
    }
    return null;
  }
}