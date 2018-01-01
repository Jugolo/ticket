<?php
namespace Lib\Tempelate;

class TempelateDirLoader{
  private $path;
  
  public function __construct(string $path){
    $this->path = BASE.$path;
  }
  
  public function exists() : bool{
    return is_dir($this->path);
  }
  
  public function getDir() : string{
    return $this->path;
  }
  
  public function containsFile(string $file){
    return file_exists($this->path.$file) && is_file($this->path.$file);
  }
  
  public function getFile(string $file) : TempelateFileLoader{
    if($this->containsFile($file))
      return new TempelateFileLoader($this->path.$file);
    throw new TempelateFileNotFound($this->path.$file." was not found");
  }
}