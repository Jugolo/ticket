<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;

class Tokenizer{
  private $reader;
  private $buffer;
  private $controler = "";
  private $systemKeyword = [
    "and",
    "or",
    "not",
    "as",
    "config",
    "access",
    "else",
    "elseif",
    "loggedIn",
    "endblock",
    "pageAccess",
    ];
  
  public function __construct(TempelateFileReader $reader, TempelateControler $controler){
    $this->reader    = $reader;
    $this->controler = $controler;
  }
  
  public function current() : TokenBuffer{
    if(!$this->buffer)
      $this->next();
    return $this->buffer;
  }
  
  public function next() : TokenBuffer{
    return $this->buffer = $this->generate();
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
      case "@":
        return $this->buffer("PUNCTOR", "@");
      case "?":
        return $this->buffer("PUNCTOR", "?");
      case ".":
        return $this->buffer("PUNCTOR", ".");
      case ":":
        return $this->buffer("PUNCTOR", ":");
      case "(":
        return $this->buffer("PUNCTOR", "(");
      case ")":
        return $this->buffer("PUNCTOR", ")");
      case "-":
        if($this->reader->peek() == "!"){
          $this->reader->read();
          return $this->buffer("ECB", "End code block");
        }
        return $this->buffer("PUNCTOR", "-");
      case "!":
        if($this->reader->eof() || $this->reader->peek() != "=")
          throw new TempelateException("Unexpected char !", $this->reader->getFile(), $this->reader->getLine());
        $this->reader->read();
        return $this->buffer("PUNCTOR", "!=");
      case "=":
        if(!$this->reader->eof() && $this->reader->peek() == "="){
          $this->reader->read();
          return $this->buffer("PUNCTOR", "==");
        }
        return $this->buffer("PUNCTOR", "=");
      case "[":
        return $this->buffer("PUNCTOR", "[");
      case "]":
        return $this->buffer("PUNCTOR", "]");
    }
    throw new TempelateException("Unexpected char '{$code}'(".ord($code).")", $this->reader->getFile(), $this->reader->getLine());
  }
  
  private function number(string $char){
    $c = ord($char);
    return $c >= 48 && $c <= 57;
  }
  
  private function getString(string $end){
    $str = "";
    while(true){
      if($this->reader->eof())
        throw new TempelateException("Missing end of string. Get end of file", $this->reader->getFile(), $this->reader->getLine());
      $char = $this->reader->read();
      if($end == $char)
        break;
      $str .= $char;
    }
    return $this->buffer("STRING", $str);
  }
  
  private function getNumber(string $char){
    $number = $char.$this->getRawNumber();
    if(!$this->reader->eof() && $this->reader->peek() == "."){
      $this->reader->read();
      $number .= ".".$this->getRawNumber();
    }
    return $this->buffer("NUMBER", $number);
  }
  
  private function getRawNumber(){
    $result = "";
    while(!$this->reader->eof() && $this->number($this->reader->peek())){
      $result .= $this->reader->read();
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
    while(!$this->reader->eof() && $this->variabel($this->reader->peek()))
      $identify .= $this->reader->read();
     
    if($this->controler->hasControler($identify) || in_array($identify, $this->systemKeyword))
      return $this->buffer("KEYWORD", $identify);
    /*
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
      "pageAccess",
      "access",
      "endblock",
      "loggedIn",
      "foreach",
      "as",
      "echo",
      "set",
      "or",
      "and",
      "not",
      "language",
      "plugin",
      ]))
       return new TokenBuffer("KEYWORD", $identify);*/
    
    return $this->buffer("IDENTIFY", $identify);
  }
  
  private function toNextChar(){
    while(!$this->reader->eof()){
      $char = $this->reader->read();
      if($char == " " || $char == "\r"){
        continue;
      }
      return $char;
    }
    return null;
  }
  
  private function buffer(string $type, string $value){
    return new TokenBuffer($type, $value, $this->reader);
  }
}