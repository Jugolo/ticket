<?php
namespace Lib;

use Lib\Exception\CacheException;

class Cache{
  public static function exists(string $name) : bool{
    return file_exists(self::getTempName($name));
  }
  
  public static function delete(string $name) : bool{
    if(!self::exists($name)){
      return true;
    }
    
    unlink(self::getTempName($name));
    return true;
  }
  
  public static function get(string $name){
    if(!self::exists($name))
      return "";
    $name = self::getTempName($name);
    $stream = fopen($name, "r");
    $type = fread($stream, 1);
    $fsize = filesize($name)-1;
    $context = "";
    if($fsize > 0){
      $context = fread($stream, $fsize);
    }
    fclose($stream);
    
    switch($type){
      case 'S':
        return $context;
      case 'A':
        return json_decode($context, true);
      default:
        throw new CacheException("Unknown cache type '{$type}'");
    }
  }
  
  public static function create(string $name, $data) : bool{
    if(self::exists($name)){
      return false;
    }
	
	//at some point a temp folder is not exists. Create one 
	if(!file_exists("Lib/Temp"))
		mkdir("Lib/Temp");
    
    if(is_array($data))
      $data = "A".json_encode($data);
    elseif(is_string($data))
      $data = "S".$data;
    else
      throw new CacheException("Unknown cache data type '".gettype($data)."'");
    
    $name = self::getTempName($name);
    $fopen = fopen($name, "w");
    fwrite($fopen, $data);
    fclose($fopen);
    return true;
  }
  
  private static function getTempName(string $name) : string{
    return "Lib/Temp/".md5($name).".temp";
  }
}