<?php
namespace Lib\Setup;

use Lib\Report;
use Lib\Ajax;
use Lib\Config;
use Lib\Language\Language;

class Main{
  const SETUP_VERSION = "V4.2";
  
  public static function controle(){
    if(Ajax::isAjaxRequest()){
      return;
    }
    global $user;
    Language::load("setup");
    if(self::needInstall()){
      define("IN_SETUP", true);
      Install::install();
    }elseif(self::needUpgrade()){
      Upgrade::upgrade();
    }elseif($user->isLoggedIn()){
      Report::error(Language::get("REMOVE_SETUP"));
    }
  }
  
  private static function needInstall() : bool{
    return !file_exists("./config.php");
  }
  
  private static function needUpgrade() : bool{
    return version_compare(Main::SETUP_VERSION, Config::get("version"), '>');
  }
}
