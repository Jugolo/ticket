<?php
namespace Lib\Ext\Page\HandleTickets;

use Lib\Controler\Page\PageInfo;

class Info implements PageInfo{
  public function menuVisible() : bool{
    return defined("user") && getUsergroup(user["groupid"])["handleTickets"] == "1";
  }
  
  public function pageVisible() : bool{
    return $this->menuVisible();
  }
  
  public function name() : string{
    return "handleTickets";
  }
  
  public function title() : string{
    return "Tickets";
  }
}
