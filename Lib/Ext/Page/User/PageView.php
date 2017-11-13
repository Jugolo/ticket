<?php
namespace Lib\Ext\Page\User;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Report;
use Lib\Plugin\Plugin;
use Lib\Tempelate;

class PageView implements P{
  public function body(Tempelate $tempelate){
    if(!empty($_GET["sub"])){
      $this->changegroup($tempelate);
    }else{
      if(!empty($_GET["delete"])){
        $this->deleteuser(intval($_GET["delete"]));
      }
      $group = getUsergroup(user["groupid"]);
      $query = Database::get()->query("SELECT `id`, `username` FROM `user`");
      $list = [];
      while($row = $query->fetch())
        $list[] = $row->toArray();
      $tempelate->put("users", $list);
      $tempelate->render("user");
    }
  }
  
  private function deleteuser(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `comment` WHERE `uid`=".$id);
    $query = $db->query("SELECT `id` FROM `ticket` WHERE `uid`='".$id."'");
    $query->render(function($row){
      Plugin::trigger_event("system.ticket.delete", $row->id);
    });
    $db->query("DELETE FROM `user` WHERE `id`='".$id."'");
    $db->query("DELETE FROM `notify_setting` WHERE `uid`='{$id}'");
    Report::okay("The user is now deleted");
    header("location: ?view=users");
    exit;
  }
  
  private function changegroup(Tempelate $tempelate){
    if(empty($_GET["uid"])){
      notfound();
      return;
    }
  
    $group = getUsergroup(user["groupid"]);
    if($group["changeGroup"] != 1){
       notfound();
       return;
    }
  
    $db = Database::get();
    //wee found the user now
    $user = $db->query("SELECT `id`, `username`, `groupid` FROM `user` WHERE `id`='".$db->escape($_GET["uid"])."'")->fetch();
    if(!$user){
      notfound();
      return;
    }
  
    if(!empty($_GET["gid"])){
      updateUserGroup($user, $_GET["gid"]);
      Report::okay("The users group is now updated");
      header("location: ?view=users&sub=group&uid=".$_GET["uid"]);
      exit;
    }
  
    $query = $db->query("SELECT * FROM `group`");
    $groups = [];
    while($row = $query->fetch()){
      $groups[] = [
        "id"         => $row->id,
        "name"       => $row->name,
        "is_current" => $row->id == $user->groupid
        ];
    }
    $tempelate->put("groups", $groups);
    
    $tempelate->put("g_id",       $user->id);
    $tempelate->put("g_username", $user->username);
    $tempelate->put("owen",       $user->id == user["id"]);
    
    $tempelate->render("change_group");
  }
}