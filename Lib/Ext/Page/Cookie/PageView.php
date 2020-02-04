<?php
namespace Lib\Ext\Page\Cookie;

use Lib\Controler\Page\PageView as p;
use Lib\Tempelate;
use Lib\Page;
use Lib\Language\Language;
use Lib\Plugin\Plugin;

class PageView implements p{
  public function loginNeeded() : string{
    return "BOTH";
  }
  
  public function identify() : string{
    return "cookie";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    Language::load("about_cookie");
    
    $list = new CookieListAdd();
    $list->add("sess_id", Language::get("SESSION"));
    $list->add("accept_cookie", Language::get("A_COOKIE"));
    Plugin::trigger_event("system.page.aboutcookie.add", $list);
    $tempelate->put("cookie_list", $list->toArray());
    
    $tempelate->render("cookie");
  }
}