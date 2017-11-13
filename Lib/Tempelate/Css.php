<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;

class Css{
  private $files = [];
  
  public function addFile(string $file){
    if(!file_exists($file)){
      throw new TempelateException("Unknown style file '{$file}'");
    }
    
    $this->files[] = $file;
  }
  
  public function getStyleSources(){
    $f = "";
    foreach($this->files as $file){
      $f .= $this->getSource($file);
    }
    
    echo "<style>\r\n  {$f}\r\n</style>";
  }
  
  public function getFile(string $file){
    if(!file_exists($file))
      throw new TempelateException("Missing includes style file '{$file}'");
    return $this->getSource($file);
  }
  
  private function getSource(string $file) : string{
     $self = $this;
     return preg_replace_callback("/--include\((.*?)\);/", function($e) use($self, $file){
       return $self->getFile(dirname($file)."/".$e[1]);
     }, file_get_contents($file));
  }
}