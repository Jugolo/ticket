<?php
namespace Lib\Ext\Page\Tempelate;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    if(!defined("user"))
      return false;
    return group["handleTempelate"] == 1;
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "tempelate";
  }
  
  public function title() : string{
    return "Tempelate";
  }
}