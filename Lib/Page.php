<?php
namespace Lib;

use Lib\Controler\Page\PageView;

class Page{
  private $controlers = [];
  
  public function __construct(){
    //load all files 
    $dir = "Lib/Ext/Page/";
    $stream = opendir($dir);
    while($item = readdir($stream)){
      if($item == "." || $item == "..")
        continue;
      if(is_dir($dir.$item)){
        $this->controleDir($dir.$item."/");
      }
    }
  }
  
  public function hasAccessTo(string $identify) : bool{
    if(empty($this->controlers[$identify]))
      return false;
  
    $page = $this->controlers[$identify];
    if($page->loginNeeded() == "YES" && !defined("user") || $page->loginNeeded() == "NO" && defined("user"))
      return false;
    
    return $this->hasAccess($page->access());
  }
  
  public function show(string $identify, Tempelate $tempelate){
    if(empty($this->controlers[$identify]))
      $this->notfound($tempelate);
    
    $page = $this->controlers[$identify];
    if($page->loginNeeded() == "YES" && !defined("user") || $page->loginNeeded() == "NO" && defined("user"))
      $this->accessdenid($tempelate);
    
    if(!$this->hasAccess($page->access()))
      $this->accessdenid($tempelate);
    
    $page->body($tempelate, $this);
  }
  
  private function notfound(Tempelate $tempelate){
    if($tempelate->hasControler()){
      $controler = $tempelate->getControler();
      if($controler->hasError() && ($page = $controler->error()->notfound())){
        $tempelate->render($page, $this);
        exit;
      }
    }
    $tempelate = new Tempelate("");
    $tempelate->put("error", "The requested page was not found");
    $tempelate->render("error", $this);
    exit;
  }
  
  private function accessdenid(Tempelate $tempelate){
    if($tempelate->hasControler()){
      $controler = $tempelate->getControler();
      if($controler->hasError() && ($page = $controler->error()->accessdenid())){
        $tempelate->render($page, $this);
        exit;
      }
    }
    $tempelate = new Tempelate("");
    $tempelate->put("error", "Access denied");
    $tempelate->render("error", $this);
    exit;
  }
  
  private function hasAccess(array $access){
    if(count($access) == 0)
      return true;
    foreach($access as $name){
      if(Access::userHasAccess($name))
        return true;
    }
    return false;
  }
  
  private function controleDir(string $dir){
    if(!file_exists($dir."PageView.php")){
      return;
    }
    include $dir."PageView.php";
    
    $class = str_replace("/", "\\", $dir)."PageView";
    if(!class_exists($class))
      return;
    
    $obj = new $class();
    if(!($obj instanceof PageView))
      return;
    $this->controlers[$obj->identify()] = $obj;
  }
}