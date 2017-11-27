<?php
namespace Lib\Ext\Page\Tempelate;

use Lib\Controler\Page\PageView as P;
use Lib\Tempelate;
use Lib\Config;
use Lib\Report;
use Lib\Log;
use Lib\Page;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "tempelate";
  }
  
  public function access() : array{
    return [
      "TEMPELATE_SELECT"
    ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    if(!empty($_GET["select"]))
      $this->select($_GET["select"]);
    $tempelate->put("tempelates", $this->getTempelate());
    $tempelate->render("tempelate", $page);
  }
  
  private function select(string $name){
    if(is_dir("Lib/Tempelate/Style/".$name)){
      Config::set("tempelate", $name);
      Report::okay("Tempelate is updated");
      Log::system("%s updated tempelate to %s", user["username"], $name);
      header("location: ?view=tempelate");
      exit;
    }else{
      Report::error("Unknown tempelate");
    }
  }
  
  private function getTempelate(){
    $tempelates = [];
    $dir = "Lib/Tempelate/Style/";
    $stream = opendir($dir);
    while($item = readdir($stream)){
      if($item == "." || $item == "..")
        continue;
      
      if(is_dir($dir.$item)){
        $tempelates[] = [
          "name"       => $item,
          "is_current" => Config::get("tempelate") == $item
          ];
      }
    }
    
    closedir($stream);
    return $tempelates;
  }
}