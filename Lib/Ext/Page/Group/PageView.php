<?php
namespace Lib\Ext\Page\Group;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Report;
use Lib\Config;
use Lib\Tempelate;

class PageView implements P{
  public function body(Tempelate $tempelate){
    if(!empty($_GET["sub"]) && $this->sub($_GET["sub"], $tempelate)){
      return;
    }
    if(!empty($_POST["name"])){
      $this->create($_POST["name"]);
    }
    $standart = Config::get("standart_group");
    $query = Database::get()->query("SELECT `id`, `name` FROM `group`");
    $groups = [];
    while($row = $query->fetch())
      $groups[] = $row->toArray();
    $tempelate->put("groups", $groups);
    
    $tempelate->put("standart", $standart);
    
    $tempelate->render("group");
  }
  
  private function sub(string $sub, Tempelate $tempelate) : bool{
    if(!file_exists("Lib/Ext/Page/Group/Sub/".$sub.".php")){
      return false;
    }
    call_user_func([
      "Lib\\Ext\\Page\\Group\\Sub\\".$sub,
      "body"
      ], $tempelate);
    
    return true;
  }
  
  private function create(string $name){
     $db = Database::get();
     $id = $db->query("INSERT INTO `group` (
      `name`,
      `showTicket`,
      `changeGroup`,
      `handleGroup`,
      `handleTickets`,
      `showError`,
      `showProfile`,
      `closeTicket`,
      `changeFront`,
      `changeSystemName`
    ) VALUES (
      '".$db->escape($_POST["name"])."',
      '0',
      '0',
      '0',
      '0',
      '0',
      '0',
      '0',
      '0',
      '0'
    );");
    Report::okay("The group is created");
    header("location: ?view=handleGroup&sub=Access&gid=".$id);
    exit;
  }
}