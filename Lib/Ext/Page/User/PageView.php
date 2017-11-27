<?php
namespace Lib\Ext\Page\User;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Report;
use Lib\Plugin\Plugin;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\User\Auth;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "users";
  }
  
  public function access() : array{
    return [
      "USER_GROUP",
      "USER_DELETE",
      "USER_PROFILE"
    ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    if(!empty($_GET["sub"]) && Access::userHasAccess("USER_GROUP")){
      $this->changegroup($tempelate, $page);
    }else{
      if(!empty($_GET["delete"]) && Access::userHasAccess("USER_DELETE")){
        $this->deleteuser(intval($_GET["delete"]));
      }
      $group = getUsergroup(user["groupid"]);
      $query = Database::get()->query("SELECT `id`, `username` FROM `user`");
      $list = [];
      while($row = $query->fetch())
        $list[] = $row->toArray();
      $tempelate->put("users", $list);
      $tempelate->render("user", $page);
    }
  }
  
  private function deleteuser(int $id){
    Auth::deleteUser($id);
    Report::okay("The user is now deleted");
    header("location: ?view=users");
    exit;
  }
  
  private function changegroup(Tempelate $tempelate, Page $page){
    if(empty($_GET["uid"])){
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
    
    $tempelate->render("change_group", $page);
  }
}