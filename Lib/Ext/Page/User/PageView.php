<?php
namespace Lib\Ext\Page\User;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Report;
use Lib\Plugin\Plugin;
use Lib\Tempelate;
use Lib\Page;
use Lib\User\User;
use Lib\User\Auth;
use Lib\Language\Language;
use Lib\Ajax;
use Lib\Request;

class PageView implements P{
  public function __construct(){
	  Plugin::addEvent("ajax.change_group", [$this, "ajax_changegroup"]);
  }
	
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
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    Language::load("user");
    if(!empty($_GET["sub"]) && $user->access()->has("USER_GROUP")){
      $this->changegroup($tempelate, $page, $user);
    }else{
      if(!empty($_GET["delete"]) && $user->access()->has("USER_DELETE")){
        $this->deleteuser(intval($_GET["delete"]));
      }
      $query = Database::get()->query("SELECT `id`, `username` FROM `".DB_PREFIX."user`");
      $list = [];
      while($row = $query->fetch())
        $list[] = $row->toArray();
      $tempelate->put("users", $list);
      $tempelate->render("user");
    }
  }
  
  private function deleteuser(int $id){
    Auth::deleteUser($id);
    Report::okay(Language::get("USER_DELETED"));
    header("location: ?view=users");
    exit;
  }
  
  private function changegroup(Tempelate $tempelate, Page $page, User $user){
    if(empty($_GET["uid"])){
      notfound();
      return;
    }
  
    $db = Database::get();
    //wee found the user now
    $data = $db->query("SELECT `id`, `username` FROM `".DB_PREFIX."user` WHERE `id`='".$db->escape($_GET["uid"])."'")->fetch();
    if(!$data){
      notfound();
      return;
    }
	
	$query = $db->query("SELECT g.id, g.name FROM `".DB_PREFIX."group` AS g LEFT JOIN `".DB_PREFIX."grup_member` AS member ON g.id=member.gid WHERE member.uid='".(int)$data->id."';");
    $member = [];
	while($row = $query->fetch()){
		$member[] = [
		  "id"   => $row->id,
		  "name" => $row->name
		];
	}
	$tempelate->put("member", $member);
	
	$query = $db->query("select * from `".DB_PREFIX."group` AS g where not exists ( select 1 from `".DB_PREFIX."grup_member` where gid = g.id AND `uid`='".$data->id."' );");
	$notmember = [];
	while($row = $query->fetch()){
		$notmember[] = [
		   "id"   => $row->id,
		   "name" => $row->name
		];
	}
	$tempelate->put("notmember", $notmember);
    $tempelate->render("change_group");
    return;
  
    if(!empty($_GET["gid"])){
      $gid = (int)$_GET["gid"];
      $db->query("UPDATE `".DB_PREFIX."user` SET `groupid`='{$gid}' WHERE `id`='{$data->id}';");
      
      header("location: ?view=users&sub=group&uid=".$_GET["uid"]);
      exit;
    }
	
	
  
    $query = $db->query("SELECT * FROM `".DB_PREFIX."group`");
    $groups = [];
    while($row = $query->fetch()){
      $groups[] = [
        "id"         => $row->id,
        "name"       => $row->name,
        "is_current" => $row->id == $user->groupid
        ];
    }
    $tempelate->put("groups", $groups);
    
    $tempelate->put("g_id",       $data->id);
    $tempelate->put("g_username", $data->username);
    $tempelate->put("owen",       $data->id == $user->id());
    
    $tempelate->render("change_group");
  }
  
  public function ajax_changegroup(){
	  Language::load("user");
	  global $user;
	  if(!$user->access()->has("USER_GROUP")){
		  Ajax::set("success", false);
		  Report::error(Language::get("ACCESS_DENIAD"));
		  return;
	  }
	  
	  if(Request::isEmpty(Request::POST, "uid") || Request::isEmpty(Request::POST, "gid") || Request::isEmpty(Request::POST, "add")){
		  Ajax::set("success", false);
		  return;
	  }
	  
	  $db = Database::get();
	  if(Request::toString(Request::POST, "add") == "true"){
		  $db->insert("grup_member", [
			"gid" => Request::toInt(Request::POST, "gid"),
			"uid" => Request::toInt(Request::POST, "uid")
		  ]);
	  }else{
		  $db->query("DELETE FROM `".DB_PREFIX."grup_member` 
		              WHERE `uid`='".$db->escape(Request::toInt(Request::POST, "uid"))."'
		              AND `gid`='".$db->escape(Request::toInt(Request::POST, "gid"))."'");
	  }
	  Report::okay(Language::get("GROUP_UPDATED"));
	  Ajax::set("success", true);
  }
}
