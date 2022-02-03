<?php
namespace Lib\Ext\Page\Front;

use Lib\Controler\Page\PageView as P;
use Lib\Config;
use Lib\Bbcode\Parser;
use Lib\Report;
use Lib\Cache;
use Lib\Tempelate;
use Lib\Page;
use Lib\User\User;
use Lib\Language\Language;

class PageView implements P{
  public function loginNeeded() : string{
    return "BOTH";
  }
  
  public function access() : array{
    return [];
  }
  
  public function identify() : string{
    return "front";
  }
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    Language::load("front");
    if($user->isLoggedIn() && !empty($_GET["logout"]) && $_GET["logout"] == session_id()){
      session_destroy();
      header("location: ?view=front");
      exit;
    }
    
    $access = $user->access();
    if($access->has("SYSTEM_FRONT") && !empty($_GET["change"])){
      $this->changeFront($tempelate);
      return;
    }
    if($access->has("SYSTEM_NAME") && !empty($_GET["changeSystemName"])){
       $this->changeSystemNameEditor($tempelate);
       return;
    }
    
    if(Cache::exists("front")){
      $tempelate->put("front", Cache::get("front"));
    }else{
      $parser = new Parser(Config::get("front"), $tempelate);
      $front = $parser->getHtml();
      Cache::create("front", $front);
      $tempelate->put("front", $front);
    }
    
    $tempelate->render("front");
  }
  
  private function changeSystemNameEditor(Tempelate $tempelate){
    if(!empty($_POST["systemname"]) && trim($_POST["systemname"])){
      Config::set("system_name", $_POST["systemname"]);
      Report::okay(Language::get("S_NAME_UPDATED"));
      header("location: #");
      exit;
    }
    $tempelate->render("change_systenName");
  }
  
  private function changeFront(Tempelate $tempelate){
    if(!empty($_GET["update"])){
      $front = empty($_POST["context"]) ? "" : $_POST["context"];
      Config::set("front", $front);
      Cache::delete("front");
      Report::okay(Language::get("FRONT_UPDATED"));
      header("location: ?view=front");
      exit;
    }
    $tempelate->put("front", Config::get("front"));
    $tempelate->render("change_front");
  }
}
