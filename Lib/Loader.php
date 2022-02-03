<?php
namespace Lib;

class Loader{
  public static function set(){
    spl_autoload_register(function($class){
      if(!class_exists($class)){
		  $path = BASE.str_replace("\\", "/", $class).".php";
		  if(file_exists($path))
			  include $path;
      }
    });
  }
}