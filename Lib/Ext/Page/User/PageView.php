<?php
namespace Lib\Ext\Page\User;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Okay;

class PageView implements P{
  public function body(){
    if(!empty($_GET["sub"])){
      $this->changegroup();
    }else{
      if(!empty($_GET["delete"])){
        $this->deleteuser(intval($_GET["delete"]));
      }
      $group = getUsergroup(user["groupid"]);
      $query = Database::get()->query("SELECT `id`, `username` FROM `user`");
      while($row = $query->fetch()){
        echo two_container(
          $group["showProfile"] == 1 ? "<a href='?view=profile&user={$row->id}'>".htmlentities($row->username)."</a>" : htmlentities($row->username), 
          ($group["changeGroup"] == 1 ? "<a href='?view=users&sub=group&uid=".$row->id."'>Change group</a>" : "").($row->id == user["id"] ? "" : " <a href='?view=users&delete={$row->id}'>Delete</a>")
        );
      }
    }
  }
  
  private function deleteuser(int $id){
    $db = Database::get();
    $db->query("DELETE FROM `comment` WHERE `uid`=".$id);
    $query = $db->query("SELECT `id` FROM `ticket` WHERE `uid`='".$id."'");
    while($row = $query->fetch()){
      $db->query("DELETE FROM `ticket_track` WHERE `tid`='".$row->id."'");
      $db->query("DELETE FROM `ticket_value` WHERE `hid`='".$row->id."'");
      $db->query("DELETE FROM `comment` WHERE `tid`='".$row->id."'");
    }
    $db->query("DELETE FROM `ticket` WHERE `uid`='".$id."'");
    $db->query("DELETE FROM `user` WHERE `id`='".$id."'");
    $db->query("DELETE FROM `notify` WHERE `uid`='{$id}'");
    $db->query("DELETE FROM `notify_setting` WHERE `uid`='{$id}'");
    Okay::report("The user is now deleted");
    header("location: ?view=users");
    exit;
  }
  
  private function changegroup(){
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
      Okay::report("The users group is now updated");
      header("location: ?view=users&sub=group&uid=".$_GET["uid"]);
      exit;
    }
  
    echo "<h3>Change group for ".htmlentities($user->username)."</h3>";
    if(user["id"] == $user->id){
      echo "<h3 class='notokay'>You looking of you owen membership of this group!</h3>"; 
    }
  
    $query = $db->query("SELECT * FROM `group`");
    while($row = $query->fetch()){
      $options = [];
      if($row->id == $user->groupid){
        $options["tag2class"] = "notokay";
        $two = "Chose";
      }else{
        $two = "<a href='?view=users&sub=group&uid=".$user->id."&gid=".$row->id."' class='okay'>Chose</a>";
      }
      echo two_container(htmlentities($row->name), $two, $options);
    }
  }
}