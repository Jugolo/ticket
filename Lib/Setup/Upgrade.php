<?php
namespace Lib\Setup;

use Lib\Config;

use Lib\Error;
use Lib\Okay;

class Upgrade{
  public static function upgrade(){
    $upgrade = new Upgrade(function($c){
      if($c->upgrade()){
        Okay::report("Upgraded to ".$c->version);
        return true;
      }else{
        Error::report("Failed to upgrade to ".$c->version);
        return false;
      }
    });
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