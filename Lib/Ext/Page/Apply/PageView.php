<?php
namespace Lib\Ext\Page\Apply;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Report;
use Lib\Age;
use Lib\Tempelate;
use Lib\Page;
use Lib\Ticket\Ticket;
use Lib\Language\Language;
use Lib\File\FileExtension;
use Lib\User\User;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "apply";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    Language::load("apply");
    if(empty($_GET["to"]) || !($data = $this->data($user))){
      $this->select_to($tempelate, $user);
      return;
    }
  
    if($data["age"]){
      if(($respons = Age::controle($user, $data["age"], $data["name"])) != Age::NO_ERROR){
        switch($respons){
          case AGE::GET_AGE:
            Age::get_age($user, $data["name"], $tempelate, $page);
            return;
          default:
            header("location: ?view=apply");
            exit;
        }
      }
    }
  
    if(!empty($_GET["done"])){
      $this->controle_apply($data, $user);
    }
    
    $tempelate->put("name",        $data["name"]);
    $tempelate->put("category_id", $data["id"]);
    
    $query = Database::get()->query("SELECT * FROM `".DB_PREFIX."category_item` WHERE `cid`='{$data["id"]}'");
    $field = [];
    $save = new SaveInputs($data["id"]);
    while($row = $query->fetch()){
      $data = $row->toArray();
      if($row->type == 3){
        $explode = explode(",", $row->placeholder);
        $placeholder = [];
        for($i=0;$i<count($explode);$i++){
          $placeholder[] = [
            "id"    => $i,
            "value" => trim($explode[$i])
            ];
        }
        $data["placeholder"] = $placeholder;
      }
      $data["saved"] = $save->get($row->id);
      $field[] = $data;
    }
    $save->delete();
    $tempelate->put("field", $field, $page);
    
    $tempelate->render("apply");
  }
  
  private function controle_apply(array $data, User $user){
    $db = Database::get();
    $errcount = 0;
    $query = $db->query("SELECT * FROM `".DB_PREFIX."category_item` WHERE `cid`='".$data["id"]."'");
    $fields = [];
    $saver = new SaveInputs($data["id"]);
    $extension = new FileExtension();
    while($row = $query->fetch()){
      if($row->type == 4){
        //this is a file
        if(!array_key_exists($row->id, $_FILES) || $_FILES[$row->id]["error"] != UPLOAD_ERR_OK){
          Report::error(Language::get("F_MISSING", [htmlentities($row->text)]));
          $errcount++;
        }else{
          if(!$extension->isSupported($row->placeholder, $_FILES[$row->id]["name"])){
            Report::error(Language::get("F_SUPPORT", [$row->text]));
            $errcount++;
          }else{
            $fields[] = [
              "text"  => $row->text,
              "type"  => $row->type,
              "value" => [$_FILES[$row->id]["tmp_name"], get_extension($_FILES[$row->id]["name"])]
            ];
          }
        }
      }elseif(!array_key_exists($row->id, $_POST)){
        Report::error(Language::get("F_MISSING", [htmlentities($row->text)]));
        $errcount++;
      }elseif($row->type == 3){
        $count = count($options = explode(",", $row->placeholder)) - 1;
        $value = (int)$_POST[$row->id];
        if($value < 0 || $value > $count){
          Report::error(Language::get("A_MISSING", [htmlentities($row->text)]));
          $errcount++;
        }else{
          $fields[] = [
            "text"  => $row->text,
            "type"  => 3,
            "value" => $options[$value]
          ];
          $saver->put($row->id, $options[$value]);
        }
      }else{
        if(!trim($_POST[$row->id])){
          $errcount++;
          Report::error(Language::get("A_MISSING", [htmlentities($row->text)]));
        }else{
          $fields[] = [
            "text"  => $row->text,
            "type"  => $row->type,
            "value" => $_POST[$row->id]
          ];
          $saver->put($row->id, $_POST[$row->id]);
        }
      }
    }
    
    if($errcount !== 0 || count($fields) == 0){
      if(count($fields) == 0 && $errcount == 0){
        Report::error(Language::get("EMPTY_TICKET"));
      }
      $saver->save();
      header("location: ?view=apply&to=".$data["id"]);
      exit;
    }else{
      $id = Ticket::createTicket($user->id(), $data["id"], $fields);
      Report::okay(Language::get("TICKET_SAVED"));
      header("location: ?view=tickets&ticket_id=".$id);
      exit;
    }
  }
  
  private function select_to(Tempelate $tempelate, User $user){
    if(!empty($_GET["to"])){
      notfound();
      return;
    }
    
    $query = Database::get()->query("SELECT cat.* FROM `".DB_PREFIX."catogory` AS cat
                                     LEFT JOIN `".DB_PREFIX."category_access` AS access ON cat.id=access.cid
                                     LEFT JOIN `".DB_PREFIX."grup_member` AS member ON access.gid=member.gid
                                     WHERE member.uid='".$user->id()."'
                                     AND access.name='APPLY_CAT'
                                     AND cat.open='1'
                                     ORDER BY cat.sort_ordre ASC");
    
    $options = [];
    $count = 0;
    $lastID = 0;
    while($row = $query->fetch()){
	  $birth = $user->birth();
      if($row->age && $birth){
        if($birth->age() < $row->age){
         continue; 
        }
      }
      $options[] = [
        "id"   => $row->id,
        "name" => $row->name
        ];
      $lastID = $row->id;
      $count++;
    }
         
    if($count == 0){
      $tempelate->put("apply_error", Language::get("NO_CAT"));
      $tempelate->render("apply_error");
      return;   
    }
    
    if($count == 1){
      header("location: ?view=apply&to=".$lastID);
      exit;
    }
    
    $tempelate->put("category", $options);
    $tempelate->render("select_to");
  }
  
  private function data(User $user) : ?array{
    $db = Database::get();
    if($data = $db->query("SELECT cat.* FROM `".DB_PREFIX."catogory` AS cat
                           LEFT JOIN `".DB_PREFIX."category_access` AS access ON cat.id=access.cid
                           LEFT JOIN `".DB_PREFIX."grup_member` AS member ON access.gid=member.gid
                           WHERE cat.id='".$db->escape($_GET["to"])."'
                           AND member.uid='".$user->id()."'
                           AND access.name='APPLY_CAT'
                          ")->fetch()){
      return $data->toArray();
    }
    return null;
  }
  
  private function sendEamilToAdmin(string $catName){
    $email = new Email();
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email
                         FROM `".DB_PREFIX."user` AS user
                         LEFT JOIN `".DB_PREFIX."access` AS access ON user.groupid=access.gid
                         WHERE access.name='TICKET_OTHER'
                         AND user.id<>'".user["id"]."'");
    while($row = $query->fetch()){
      $email->pushArg("username",        $row->username);
      $email->pushArg("ticket_category", $catName);
      $email->send("new_ticket",         $row->email);
    }
  }
}
