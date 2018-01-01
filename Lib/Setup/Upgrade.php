<?php
namespace Lib\Setup;

use Lib\Config;
use Lib\Report;
use Lib\Log;
use Lib\Language\Language;

class Upgrade{
  public static function upgrade(){
    //wee remove all cache to ensure new thinks to work
    self::purgeCache();
    $upgrade = new Upgrade(function($c){
      if($c->upgrade()){
        Config::set("version", $c->version);
        Report::okay(Language::get("UPGRADE_TO", [$c->version]));
        if(version_compare(\Lib\Config::get("version"), "V3.3", '>')){
          Log::system("LOG_SYSTEM_UPDATE", $c->version);
        }
        return true;
      }else{
        Report::error(Language::get("FAILED_UPGRADE", [$c->version]));
        return false;
      }
    });
  }
  
  private static function purgeCache(){
    $dir = "Lib/Temp/";
    if(!is_dir($dir)){
      mkdir($dir);
    }
    $stream = opendir($dir);
    while($item = readdir($stream)){
      if($item == "." || $item == "..")
        continue;
      
      if(is_dir($dir.$item))
        exit("Please remove {$dir}{$item}");
      unlink($dir.$item);
    }
  }
  
  public function __construct($callback){
    //wee find all upgrade and do the upgrades!
    $buffer = $this->init();
    foreach($buffer as $obj){
      if(!call_user_func($callback, $obj)){
        return;
      }
    }
  }
  
  private function init(){
    $current = Config::get("version");
    $buffer = [];
    $stream = opendir("./Lib/Setup/Upgrade/");
    while($name = readdir($stream)){
      if(is_file("./Lib/Setup/Upgrade/".$name)){
        $c = "\\Lib\\Setup\\Upgrade\\".substr($name, 0, strrpos($name, "."));
        $obj = new $c();
        if(version_compare($obj->version, \Lib\Config::get("version"), '>')){
          $buffer[] = $obj;
        }
      }
    }
    closedir($stream);
    if(count($buffer) <= 1){
      return $buffer;
    }
    usort($buffer, function($a, $b){
      return version_compare($a->version, $b->version);
    });
    
    return $buffer;
  }
}