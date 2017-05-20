<?php
namespace Lib\Ext\Page\Front;

use Lib\Controler\Page\PageView as P;

class PageView implements P{
  public function body(){
    if(defined("user") && !empty($_GET["logout"]) && $_GET["logout"] == session_id()){
      session_destroy();
      header("location: ?view=front");
      exit;
    }
  
    if(!file_exists("temp/front.inc")){
      echo "<h3>Hallo and welcomment to our site</h3><br>
      If you are server admin for this server so please read this:<br>
      Please make you own front page text. This is done to create './temp/front.inc' and make a page there will be shown here<br>
      Best regards the devolper";
    }else{
      include "temp/front.inc";
    }
  }
}
