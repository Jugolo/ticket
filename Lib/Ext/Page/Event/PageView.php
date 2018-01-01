<?php
namespace Lib\Ext\Page\Event;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Page;
use Lib\Request;
use Lib\Plugin\Plugin;

class PageView implements P{
  public function loginNeeded() : string{
    return "BOTH";
  }
  
  public function identify() : string{
    return "event";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    if(Request::isEmpty(Request::GET, "event")){
      $page->notfound($tempelate);
    }
    Plugin::trigger_page("page.".Request::toString(Request::GET, "event"), $tempelate, $page);
  }
}