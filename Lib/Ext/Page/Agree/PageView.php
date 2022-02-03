<?php
namespace Lib\Ext\Page\Agree;

use Lib\Controler\Page\PageView as P;
use Lib\Page;
use Lib\Tempelate;
use Lib\Language\Language;
use Lib\User\User;

class PageView implements P{
  public function loginNeeded() : string{
    return "NO";
  }
  
  public function identify() : string{
    return "agree";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    Language::load("agree");
    $tempelate->put("rules", [
      Language::get("INFORMATION") => Language::get("INFORMATION_DEC"),
      Language::get("IN_USE")      => Language::get("IN_USE_DEC"),
      Language::get("JUGOLO")      => Language::get("JUGOLO_DEC"),
      Language::get("DATA")        => Language::get("DATA_DEC"),
      Language::get("EMAIL")       => Language::get("EMAIL_DEC"),
      Language::get("COOKIE")      => Language::get("COOKIE_DEC")
    ]);
    $tempelate->render("agree");
  }
}
