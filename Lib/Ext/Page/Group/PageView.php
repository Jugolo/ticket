<?php
namespace Lib\Ext\Page\Group;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Report;
use Lib\Config;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Group;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "handleGroup";
  }
  
  public function access() : array{
    return [
      "GROUP_DELETE",
      "GROUP_ACCESS",
      "GROUP_STANDART",
      "GROUP_CREATE"
      ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    if(!empty($_GET["sub"]) && $this->sub($_GET["sub"], $tempelate, $page)){
      return;
    }
    if(!empty($_POST["name"]) && Access::userHasAccess("GROUP_CREATE")){
      $this->create($_POST["name"]);
    }
    $standart = Config::get("standart_group");
    $query = Database::get()->query("SELECT `id`, `name` FROM `group`");
    $groups = [];
    while($row = $query->fetch())
      $groups[] = $row->toArray();
    $tempelate->put("groups", $groups);
    
    $tempelate->put("standart", $standart);
    
    $tempelate->render("group", $page);
  }
  
  private function sub(string $sub, Tempelate $tempelate, Page $page) : bool{
    if(!file_exists("Lib/Ext/Page/Group/Sub/".$sub.".php")){
      return false;
    }
    if(!call_user_func([
      "Lib\\Ext\\Page\\Group\\Sub\\".$sub,
      "body"
      ], $tempelate, $page))
      return false;
    
    return true;
  }
  
  private function create(string $name){
     $id = Group::create($name);
    if($id == -1){
      Report::error("The group name is taken");
      header("location: #");
      exit;
    }
    Report::okay("The group is created");
    if(Access::userHasAccess("GROUP_ACCESS"))
      header("location: ?view=handleGroup&sub=Access&gid=".$id);
    else
      header("location: #");
    exit;
  }
}