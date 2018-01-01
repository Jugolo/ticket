<?php
namespace Lib;

class Loader{
  public static function set(){
    spl_autoload_register(function($class){
      if(!class_exists($class)){
        include BASE.str_replace("\\", "/", $class).".php";
      }
    });
  }
}