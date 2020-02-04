<?php
namespace Lib\Plugin;

use Lib\Cache;
use Lib\Database;

class PluginRender{
  public static $plugins = null;
  public static function render($call) : bool{
    if(self::$plugins === null)
      self::getList();
    foreach(self::$plugins as $path){
      if(!call_user_func($call, $path))
        return true;
    }
    return false;
  }
  
  public static function unset(){
    if(Cache::exists("PluginList"))
      Cache::delete("PluginList");
  }
  
  public static function getList(){
    if(Cache::exists("PluginList")){
      self::$plugins = Cache::get("PluginList");
      return;
    }
    $array = [];
    Database::get()->query("SELECT `path` FROM `".DB_PREFIX."plugin`")->fetch(function($path) use(&$array){
      $array[] = $path;
    });
    Cache::create("PluginList", $array);
    self::$plugins = $array;
  }
}