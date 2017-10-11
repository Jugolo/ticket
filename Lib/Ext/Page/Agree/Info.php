<?php
namespace Lib\Ext\Page\Agree;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return false;
  }
  
  public function pageVisible() : bool{
    return true;
  }
  
  public function name() : string{
    return "agree";
  }
  
  public function title() : string{
    return "Agree";
  }
}