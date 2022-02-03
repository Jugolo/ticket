<?php
namespace Lib;

class Request{
  const POST = "P";
  const GET  = "G";
  
  public static function toString(string $type, string $name){
    $value = self::getRaw($type, $name);
    
    if(!is_string($value) || !trim($value))
      return "";
    
    return $value;
  }
  
  public static function toInt(string $type, string $name) : int{
    $value = self::toString($type, $name);
    return is_numeric($value) ? (int)$value : -1;
  }
  
  public static function isEmpty(string $type, string $name) : bool{
    $context = self::getRaw($type, $name);
    if($context === null)
      return true;
    
    if(is_string($context))
      return strlen(trim($context)) == 0;
    if(is_array($context))
      return count($context) ==  0;
    return true;
  }
  
  private static function getRaw(string $type, string $name){
    switch($type){
      case Request::POST:
        if(array_key_exists($name, $_POST))
          return $_POST[$name];
        break;
      case Request::GET:
        if(array_key_exists($name, $_GET))
          return $_GET[$name];
        break;
    }
    return null;
  }
}