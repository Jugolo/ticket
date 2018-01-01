<?php
namespace Lib\Tempelate;

class TempelateFileReader{
  private $line   = 1;
  private $source = "";
  private $length;
  private $pointer = 0;
  private $file;
  
  public function __construct(string $source, string $file){
    $this->source = $source;
    $this->length = strlen($source);
    $this->file   = $file;
  }
  
  public function getFile() : string{
    return $this->file;
  }
  
  public function getLine() : int{
    return $this->line;
  }
  
  public function eof() : bool{
    return $this->length-1 < $this->pointer;
  }
  
  public function read() : string{
    $c = $this->source[$this->pointer];
    $this->pointer++;
    if($c == "\n")
      $this->line++;
    return $c;
  }
  
  public function unread() : string{
    $this->pointer--;
    $c = $this->source[$this->pointer];
    if($c == "\n")
      $this->line--;
    return $c;
  }
  
  public function peek() : string{
    return $this->source[$this->pointer];
  }
}