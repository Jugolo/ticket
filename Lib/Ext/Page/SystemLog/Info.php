<?php
namespace Lib\Ext\Page\SystemLog;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    if(!defined("user"))
      return false;
    return group["viewSystemLog"] == 1;
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "systemLog";
  }
  
  public function title() : string{
    return "System log";
  }
}