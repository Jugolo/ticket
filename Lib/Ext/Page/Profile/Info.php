<?php
namespace Lib\Ext\Page\Profile;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return false;
  }
  
  public function pageVisible() : bool{
    if(!defined("user")){
      return false;
    }
    
    if(empty($_GET["user"]) ||  $_GET["user"] == user["id"]){
      return true;
    }
    
    return getUsergroup(user["groupid"])["showProfile"] == 1;
  }
  
  public function name() : string{
    return "profile";
  }
  
  public function title() : string{
    return "Profile";
  }
}
