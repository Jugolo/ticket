<?php 
namespace Lib\Ext\Page\Apply;

use Lib\Controler\Page\PageInfo;
use Lib\Config;

class Info implements PageInfo{
  public function menuVisible() : bool{
    //no need to show this when no apply is open
    if(Config::get("cat_open") == 0){
      return false; 
    }
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