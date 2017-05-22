<?php
namespace Lib\Ext\Page\Error;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return defined("user") && getUsergroup(user["groupid"])["showError"] == "1";
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "error";
  }
  
  public function title() : string{
    return "Error";
  }
}