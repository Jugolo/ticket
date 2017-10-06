<?php
namespace Lib\Setup;

use Lib\Error;

class Main{
  const SETUP_VERSION = "V3.0";
  
  public static function controle(){
    if(is_ajax()){
      return;
    }
      
    if(self::needInstall()){
      define("IN_SETUP", true);
      Install::install();
    }elseif(self::needUpgrade()){
      Upgrade::upgrade();
    }else{
      Error::report("Please remove setup dir 'Lib/Setup'");
    }
  }
  
  private static function needInstall() : bool{
    return !file_exists("./config.php");
  }
  
  private static function needUpgrade() : bool{
    return version_compare(Main::SETUP_VERSION, \Lib\Config::get("version"), '>');
  }
}
