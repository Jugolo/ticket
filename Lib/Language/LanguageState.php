<?php
namespace Lib\Language;

use Lib\Database;
use Lib\Plugin\PluginRender;

class LanguageState{
  private $dir;
  private $code;
  private $langs = [];
  private $xml;
  
  public function __construct(string $dir){
    $this->dir = $dir;
    $dir = substr($dir, 0, strlen($dir)-1);
    $this->code = substr($dir, strrpos($dir, "/")+1);
    $self = $this;
    $this->xml = new \SimpleXMLElement(file_get_contents($dir."/info.xml"));
  }
  
  public function code() : string{
    return $this->code;
  }
  
  public function getFlagBase64(){
	  $lang = $this->xml->data->lang_flag;
	  if(!$lang)
		return "";
	  return base64_encode(file_get_contents($this->dir."/".$lang));
  }
  
  public function load(string $name){
    $path = $this->dir.$name.".php";
    $lang = [];
    if(file_exists($path))
      include $path;
    elseif(file_exists($name))
      include $name;
    else
      exit($name);
    $this->langs = array_merge($this->langs, $lang);
  }
  
  public function hasFile(string $name){
    return file_exists($this->dir.$name.".php");
  }
  
  public function get(string $key) : string{
    if(array_key_exists($key, $this->langs))
      return $this->langs[$key];
    return $key;
  }
  
  public function hasKey(string $key){
    return array_key_exists($key, $this->langs);
  }
}
