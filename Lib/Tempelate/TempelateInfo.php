<?php
namespace Lib\Tempelate;

use Lib\Exception\TempelateException;

class TempelateInfo{
  private $xml;
  private $base;
  
  public function __construct(\SimpleXMLElement $xml, string $base){
    $this->xml  = $xml;
    $this->base = $base;
  }
  
  public function hasError(){
    return !empty($this->xml->error);
  }
  
  public function error() : TempelateError{
    if($this->hasError())
      return new TempelateError($this->xml->error);
    throw new TempelateException("Missing error in tempelate style config", "<not in code>", -1);
  }
  
  public function getScripts() : array{
    $scripts = [];
    if($this->xml->script->script){
      foreach($this->xml->script->script as $script){
        $scripts[] = $this->base.str_replace(".", "/", (string)$script["src"]).".js";
      }
    }
    return $scripts;
  }
}