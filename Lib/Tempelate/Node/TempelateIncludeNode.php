<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;
use Lib\Tempelate\TempelateParser;
use Lib\Tempelate\TempelateFileLoader;
use Lib\Tempelate\TempelateControler;
use Lib\Exception\TempelateFileNotFound;
use Lib\Tempelate\TempelateStackTrace;
use Lib\Tempelate;

class TempelateIncludeNode implements TempelateNode{
  private $path = "";
  private $file = "";
  private $controler;
  private $tempelate;
  private $trace;
  
  public function __construct(string $file, string $path, TempelateControler $controler, Tempelate $tempelate, TempelateStackTrace $trace){
    $this->file      = $file;
    $this->path      = $path;
    $this->controler = $controler;
    $this->tempelate = $tempelate;
    $this->trace     = $trace;
  }
  
  public function toString() : string{
    $file = new TempelateFileLoader($this->path.$this->file.".include");
    //if the file is not exists handle it here
    if(!$file->exists()){
      //the file is not exists. wee controle the main dir (this is a good idea when it is plugin there handled here)
      $dir = $this->tempelate->getPathLoader();
      if(!$dir->containsFile($this->file.".include"))
        throw new TempelateFileNotFound($this->path.$this->file.".include was not found");
      $file = $dir->getFile($this->file.".include");
    }
    $parser = new TempelateParser($file->base(), $file->getReader(), $this->controler, $this->trace);
    return "//{$this->path}.include\r\n".$parser->getBody();
  }
}