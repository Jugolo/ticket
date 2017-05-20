<?php
namespace Lib\Ext\Page\Front;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return true;
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "front";
  }
  
  public function title() : string{
    return "Front";
  }
}
