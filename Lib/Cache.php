<?php
namespace Lib;

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
  
  public static function get(string $name) : string{
    if(!self::exists($name))
      return "";
    return file_get_contents(self::getTempName($name));
  }
  
  public static function create(string $name, string $data) : bool{
    if(self::exists($name)){
      return false;
    }
    
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