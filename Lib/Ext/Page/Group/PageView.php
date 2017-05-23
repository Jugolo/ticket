<?php
namespace Lib\Ext\Page\Group;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Okay;
use Lib\Config;

class PageView implements P{
  public function body(){
    if(!empty($_GET["sub"]) && $this->sub($_GET["sub"])){
      return;
    }
    if(!empty($_POST["name"])){
      $this->create($_POST["name"]);
    }
    $standart = Config::get("standart_group");
    $query = Database::get()->query("SELECT `id`, `name` FROM `group`");
    while($row = $query->fetch()){
      echo two_container($row->name, "<a href='?view=handleGroup&sub=Delete&gid=".$row->id."'>Delete group</a> 
      <a href='?view=handleGroup&sub=Access&gid=".$row->id."'>Change access</a>".(
      $row->id == $standart ? "" : " <a href='?view=handleGroup&sub=Standart&gid={$row->id}'>Set standart</a>"
      ));
    }
  
    echo "<hr>";
    echo "<form method='post' action='?view=handleGroup'>";
    echo "<h3>Create new group</h3>";
    echo two_container("Name", "<input type='text' name='name' placeholder='Fill the new groups name'>");
    echo "<div>";
    echo "<input type='submit' value='Create group'>";
    echo "</div>";
    echo "</form>";
  }
  
  private function sub(string $sub) : bool{
    if(!file_exists("Lib/Ext/Page/Group/Sub/".$sub.".php")){
      return false;
    }
    call_user_func([
      "Lib\\Ext\\Page\\Group\\Sub\\".$sub,
      "body"
      ]);
    
    return true;
  }
  
  private function create(string $name){
     $db = Database::get();
     $id = $db->query("INSERT INTO `group` (
      `name`,
      `showTicket`,
      `changeGroup`,
      `handleGroup`,
      `showProfile`
    ) VALUES (
      '".$db->escape($_POST["name"])."',
      '0',
      '0',
      '0',
      '0'
    );");
    Okay::report("The group is created");
    header("location: ?view=handleGroup&sub=Access&gid=".$id);
    exit;
  }
}