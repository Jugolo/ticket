<?php
namespace Lib\Setup;

class Main{
  const SETUP_VERSION = "V2.0";
  
  public static function controle(){
    if(is_ajax()){
      return;
    }
      
    if(self::needInstall()){
      Install::install();
    }elseif(self::needUpgrade()){
      
    }else{
      html_error("Please remove setup dir 'Lib/Setup'");
    }
  }
  
  private static function needInstall() : bool{
    return !file_exists("./config.php");
  }
  
  private static function needUpgrade() : bool{
    return false;
  }
}
