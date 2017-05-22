<?php
namespace Lib\Ext\Page\Group;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return defined("user") && getUsergroup(user["groupid"])["handleGroup"] == "1";
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "handleGroup";
  }
  
  public function title() : string{
    return "Group";
  }
}