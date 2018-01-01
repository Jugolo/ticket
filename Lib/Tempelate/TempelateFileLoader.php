<?php
namespace Lib\Tempelate;

class TempelateFileLoader{
  private $file;
  
  public function __construct(string $name){
    $this->file = $name;
  }
  
  public function exists() : bool{
    return file_exists($this->file) && is_file($this->file);
  }
  
  public function getPath() : string{
    return $this->file;
  }
  
  public function base() : string{
    return dirname($this->file)."/";
  }
  
  public function getReader() : TempelateFileReader{
    return new TempelateFileReader(file_get_contents($this->file), $this->file);
  }
}