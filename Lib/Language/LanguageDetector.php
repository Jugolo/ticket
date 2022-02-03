<?php
namespace Lib\Language;

use Lib\Error;
use Lib\Config;

class LanguageDetector{
  public static function detect(){
    $saved = LanguageLister::list();
    if(count($saved) == 0)
      Error::systemError("No language found in the language dir");
      
    if(self::fromCookie())
		return;
    
    if(!empty($_SERVER["HTTP_ACCEPT_LANGUAGE"])){
      $http = explode(",", trim($_SERVER["HTTP_ACCEPT_LANGUAGE"]));
      $list = [];
      for($i=0;$i<count($http);$i++){
        if (preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/', trim($http[$i]), $match)) {
          if(empty($match[2])){
            $match[2] = "1.0";
          }else{
            $match[2] = (string)floatval($match[2]);
          }
          if(empty($list[$match[2]]))
            $list[$match[2]] = [];
          $list[$match[2]][] = strtolower($match[1]);
        }
      }
      $element = self::selectFromList($list, $saved);
      if(count($element) > 0){
        Language::newState(self::getValue($element));
        return;
      }
    }
    
    Language::newState("Lib/Ext/Language/".Config::get("standart_language")."/");
  }
  
  private static function fromCookie() : bool{
	  if(empty($_COOKIE["lang"]))
		return false;

      $dir = "Lib/Ext/Language/".$_COOKIE["lang"]."/";
      if(!file_exists($dir) || !is_dir($dir))
		return false;
		
	  Language::newState($dir);
	  return true;
  }
  
  private static function getValue(array $array) : string{
    $values = array_values($array);
    $shift  = array_shift($values);
    return $shift[0];
  }
  
  private static function selectFromList(array $list, array $saved){
    $accepts = [];
    foreach($list as $list_quality => $list_value){
      $list_quality = floatval($list_quality);
      if($list_quality == 0.0)
        continue;
      foreach($saved as $saved_value){
        $saved_quality = 1.0;
        for($i=0;$i<count($list_value);$i++){
          $match = self::match($list_value[$i], $saved_value["code"]);
          if($match > 0){
            $q = (string)($list_quality * 1.0 * $match);
            if(empty($accepts[$q])){
              $accepts[$q] = [];
            }
            if(!in_array($list_value[$i], $accepts[$q]))
              $accepts[$q][] = $saved_value["dir"];
          }
        }
      }
    }
    return $accepts;
  }
  
  private static function match(string $saved, string $value){
    $saved = explode("-", $saved);
    $value = explode("-", $value);
    $n = $n=min(count($saved), count($value));
    for($i=0; $i<$n;$i++){
      if($saved[$i] !== $value[$i])
        break;
    }
    return $i === 0 ? 0 : (float)$i/count($saved);
  }
}
