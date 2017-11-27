<?php
namespace Lib\Setup;

use Lib\Report;
use Lib\Ajax;

class Main{
  const SETUP_VERSION = "V3.4";
  
  public static function controle(){
    if(Ajax::isAjaxRequest()){
      return;
    }
      
    if(self::needInstall()){
      define("IN_SETUP", true);
      Install::install();
    }elseif(self::needUpgrade()){
      Upgrade::upgrade();
    }else{
      Report::error("Please remove setup dir 'Lib/Setup'");
    }
  }
  
  private static function needInstall() : bool{
    return !file_exists("./config.php");
  }
  
  private static function needUpgrade() : bool{
    return version_compare(Main::SETUP_VERSION, \Lib\Config::get("version"), '>');
  }
}
