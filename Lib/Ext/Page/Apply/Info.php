<?php 
namespace Lib\Ext\Page\Apply;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return defined("user");
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "apply";
  }
  
  public function title() : string{
    return "Apply";
  }
}