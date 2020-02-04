<?php
namespace Lib\Language;

class LanguageLister{
  private static $current;
  private static $xml;
  public static function list(){
    $list = [];
    $dir = BASE."Lib/Ext/Language/";
    $stream = opendir($dir);
    while($item = readdir($stream)){
      if($item == "." || $item == ".." || !is_dir($dir.$item))
        continue;
      self::$current = $dir.$item."/";
      
      if(!self::isValid())
        continue;
      
      $list[] = [
          "dir"  => self::$current,
          "code" => (string)self::$xml->data->lang_code
        ];
    }
    return $list;
  }
  
  private static function isValid(){
    if(!file_exists(self::$current."info.xml")){
      return false;
    }
    
    self::$xml = new \SimpleXMLElement(file_get_contents(self::$current."info.xml"));
    if(!self::$xml->data || !self::$xml->data->lang_code){
      return false;
    }
    
    return true;
  }
}