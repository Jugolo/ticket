<?php
namespace Lib\Ext\Page\User;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    if(!defined("user")){
      return false;
    }
    return getUsergroup(user["groupid"])["changeGroup"] == 1;
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "users";
  }
  
  public function title() : string{
    return "User";
  }
}