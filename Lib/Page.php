<?php
namespace Lib;

use Lib\Controler\Page\PageView;
use Lib\Language\Language;
use Lib\User\User;

class Page{
  private $controlers = [];
  private $user;
  
  public function __construct(User $user){
	$this->user = $user;
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
    if($page->loginNeeded() == "YES" && !$this->user->isLoggedIn() || $page->loginNeeded() == "NO" && $this->user->isLoggedIn())
      return false;
    
    return $this->hasAccess($page->access());
  }
  
  public function show(string $identify, Tempelate $tempelate){
    if(empty($this->controlers[$identify]))
      $this->notfound($tempelate);
    
    $page = $this->controlers[$identify];
    if($page->loginNeeded() == "YES" && !$this->user->isLoggedIn() || $page->loginNeeded() == "NO" && $this->user->isLoggedIn())
      $this->accessdenid($tempelate);
    
    if(!$this->hasAccess($page->access()))
      $this->accessdenid($tempelate);
    
    $page->body($tempelate, $this, $this->user);
  }
  
  public function notfound(Tempelate $tempelate){
    if($tempelate->hasControler()){
      $controler = $tempelate->getControler();
      if($controler->hasError()){
        $error = $controler->error();
        if($error->hasNotFound()){
          $tempelate->render($error->notfound());
        }
      }
    }
    $tempelate = new Tempelate("");
    $tempelate->put("error", Language::get("NOT_FOUND"));
    $tempelate->render("error");
  }
  
  private function accessdenid(Tempelate $tempelate){
    if($tempelate->hasControler()){
      $controler = $tempelate->getControler();
      if($controler->hasError()){
        $error = $controler->error();
        if($error->hasAccessDenid()){
          $tempelate->render($error->accessdenid());
        }
      }
    }
    $tempelate = new Tempelate("", $this);
    $tempelate->put("error", Language::get("ACCESS_DENIAD"));
    $tempelate->render("error");
  }
  
  public function hasAccess(array $access){
    if(count($access) == 0)
      return true;
    $acc = $this->user->access();
    foreach($access as $name){
      if($acc->has($name))
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
