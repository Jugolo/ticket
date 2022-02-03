<?php
namespace Lib\Tempelate;

use Lib\Page;
use Lib\Exception\TempelateException;

class TempelateData{
  private $styles  = [];
  private $scripts = ["Lib/Tempelate/Script/CowTicket.js", "Lib/Tempelate/Script/Element.js"];
  private $page;
  
  public function __construct(Page $page, $info){
    $this->page = $page;
    if($info){
      $this->scripts = array_merge($this->scripts, $info->getScripts());
    }
  }
  
  public function addScript(string $script){
    $this->scripts[] = $script.".js";
  }
  
  public function addStyle(string $file){
    $this->styles[] = $file.".css";
  }
  
  public function getStyle(string $file, int $line) : string{
    $result = "";
    foreach($this->styles as $style){
      if(!file_exists($style))
        throw new TempelateException("Unknown style file: '{$style}'", $file, $line);
      $result .= $this->getInclude(file_get_contents($style), $style, $file, $line);
    }
    $this->styles = [];
    return "<style>{$result}</style>";
  }
  
  public function getScripts(string $file, int $line) : string{
    $result = "";
    
    foreach($this->scripts as $script){
      if(!file_exists($script))
        throw new TempelateException("Unknown script file '{$script}'", $file, $line);
      $result .= file_get_contents($script);
    }
    
    return $result ? "<script>{$result}</script>" : "";
  }
  
  public function hasAccessTo(string $name) : bool{
    return $this->page->hasAccessTo($name);
  }
  
  public function arrayGet($variabel, $key){
    $value = is_array($variabel) && array_key_exists($key, $variabel) ? $variabel[$key] : "";
    if($value == null)
		return "";
	return $value;
  }
  
  public function toFunc($func){
    if(!is_callable($func))
      return function(){};
    return $func;
  }
  
  private function getInclude(string $context, $style, string $file, int $line) : string{
    return preg_replace_callback("/--include\((.*?)\);/", function($reg) use($style, $file, $line){
      $inc = dirname($style)."/".$reg[1];
      if(!file_exists($inc))
        throw new TempelateException("unknoen include style file '{$inc}'", $file, $line);
      return file_get_contents($inc);
    }, $context);
  }
  
  public function notNull($obj){
	  return $obj == null ? "" : $obj;
  }
}
