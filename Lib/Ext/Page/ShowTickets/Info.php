<?php
namespace Lib\Ext\Page\ShowTickets;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return defined("user");
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "tickets";
  }
  
  public function title() : string{
    return "Show tickets";
  }
}