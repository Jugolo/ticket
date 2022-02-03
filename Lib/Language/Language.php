<?php
namespace Lib\Language;

use Lib\Exception\LanguageException;

class Language{
  private static $stack = [];
  
  public static function newState(string $dir){
    if(!is_dir($dir)){
      throw new LanguageException("Unknown language dir: ".$dir);
    }
    self::$stack[] = new LanguageState($dir);
  }
  
  public static function get(string $key, array $data = []){
    $value = self::render(function(LanguageState $state) use($key, $data){
      if($state->hasKey($key))
        return vsprintf($state->get($key), $data);
    });
    
    return $value ? : $key;
  }
  
  public static function load(string $name){
    $result = self::render(function(LanguageState $state) use($name){
      if($state->hasFile($name)){
        $state->load($name);
        return true;
      }
    });
    return $result === true ? true : false;
  }
  
  public static function renderPluginDir(string $dir){
    //find out if we has the language dir in the plugin
    if(!is_dir($dir."Language"))
      return;//no so there is no reason to do anythinks
    $dir .= "Language/";
    //render all language loaded
    self::render(function($node) use($dir){
      if(!is_dir($dir.$node->code()))
        return false;
      $dir .= $node->code()."/";
      $stream = opendir($dir);
      while($item = readdir($stream)){
        if(is_file($dir.$item)){
          $node->load($dir.$item);
        }
      }
    });
  }
  
  public static function getCode() : string{
    if(!self::$stack)
      return "";
    return self::render(function($stack){
      return $stack->code();
    });
  }
  
  public static function getFlagBase64() : string{
	  if(!self::$stack)
		return "";
	  return self::render(function($stack){
		 return $stack->getFlagBase64(); 
	  });
  }
  
  private static function render($callback){
    for($i=count(self::$stack)-1;$i>=0;$i--){
      $return = call_user_func($callback, self::$stack[$i]);
      if($return)
        return $return;
    }
    return null;
  }
}
