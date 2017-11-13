<?php
namespace Lib\Ext\Page\Front;

use Lib\Controler\Page\PageView as P;
use Lib\Config;
use Lib\Bbcode\Parser;
use Lib\Report;
use Lib\Cache;
use Lib\Tempelate;

class PageView implements P{
  public function body(Tempelate $tempelate){
    if(defined("user") && !empty($_GET["logout"]) && $_GET["logout"] == session_id()){
      session_destroy();
      header("location: ?view=front");
      exit;
    }
    
    if(defined("user")){
      if(group["changeFront"] == 1 && !empty($_GET["change"])){
        $this->changeFront($tempelate);
        return;
      }
      if(group["changeSystemName"] == 1 && !empty($_GET["changeSystemName"])){
         $this->changeSystemNameEditor($tempelate);
         return;
      }
    }
    //Parser::getJavascript($tempelate);
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
      Report::okay("System name is now updated");
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
      Report::okay("The front page is updated");
      header("location: ?view=front");
      exit;
    }
    $tempelate->put("front", Config::get("front"));
    $tempelate->render("change_front");
  }
}