<?php
namespace Lib\Ext\Page\Profile;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\User\Auth;
use Lib\Age;
use Lib\Report;
use Lib\Log;
use Lib\Email;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Language\Language;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "profile";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    Language::load("profile");
    $user = $this->getUser();
    if(!$user){
      Report::error(Language::get("NOT_FOUND"));
      header("location: ?view=users");
      exit;
    }
    
    if($user->id == user["id"]){
      if(!empty($_POST["update"])){
        $this->updateProfile();
      }elseif(!empty($_POST["pass"])){
        $this->updatePass();
      }elseif(!empty($_POST["birth"])){
        $this->updateAge();
      }
    }else{
      if($user->isActivatet == 0){
        if(Access::userHasAccess("USER_ACTIVATE") == 1 && !empty($_GET["activate"])){
          $this->activateUser($user->id);
        }
        $tempelate->put("not_activate", true);
      }
      if(Access::userHasAccess("USER_PROFILE") == 1){
        $log = Log::getUserLog($user->id);
        $logs = [];
        $log->render(function($time, $message) use(&$logs){
          $logs[] = [
            "time"    => $time,
            "message" => $message
          ];
        });
        $tempelate->put("logs", $logs);
      }
    }
    
    $tempelate->put("profile_username", $user->username);
    $tempelate->put("uid",              $user->id);
    $tempelate->put("email",            $user->email);
    $tempelate->put("age",              $user->birth_day ? Age::calculate($user->birth_day, $user->birth_month, $user->birth_year) : "Unknown");
    $tempelate->put("day",              $user->birth_day);
    $tempelate->put("month",            $user->birth_month);
    $tempelate->put("year",             $user->birth_year);
    $tempelate->put("group",            $user->name);
    
    $tempelate->render($user->id == user["id"] ? "owen_profile" : "other_profile");
  }
  
  private function getUser(){
    $db = Database::get();
    return $db->query("SELECT user.*, group.name
                       FROM `user`
                       LEFT JOIN `group` ON group.id=user.groupid
                       WHERE user.id='{$db->escape(!empty($_GET['user']) ? $_GET["user"] : user["id"])}'")->fetch();
  }
  
  private function updateAge(){
    $error = Report::count("ERROR");
    
    if(empty($_POST["day"]) || !trim($_POST["day"]) || !is_numeric($_POST["day"])){
      Report::error(Language::get("MISSING_B_D"));
    }
    
    if(empty($_POST["month"]) || !trim($_POST["month"]) || !is_numeric($_POST["month"])){
       Report::error(Language::get("MISSING_B_M"));
    }
    
    if(empty($_POST["year"]) || !trim($_POST["year"]) || !is_numeric($_POST["year"])){
       Report::error(Language::get("MISSING_B_Y"));
    }
    
    if($error == Report::count("ERROR")){
      $db = Database::get();
      $db->query("UPDATE `user` SET 
                  `birth_day`   = '{$db->escape($_POST["day"])}',
                  `birth_month` = '{$db->escape($_POST["month"])}',
                  `birth_year`  = '{$db->escape($_POST["year"])}'
                 WHERE `id`='".user["id"]."'");
      Report::okay(Language::get("BIRTH_UPDATED"));
    }
    
    header("location: #");
    exit;
  }
  
  private function updatePass(){
    $count = Report::count("ERROR");
    Language::load("auth");
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Report::error(Language::get("MISSING_PASSWORD"));
    }
    
    if(empty($_POST["repeat_password"]) || !trim($_POST["password"])){
      Report::error(Language::get("MISSING_R_PASSWORD"));
    }
    
    if($count == Report::count("ERROR") && $_POST["password"] != $_POST["repeat_password"]){
      Report::error(Language::get("PASSWORD_N_EQUEL"));
    }
    
    if(empty($_POST["current_password"]) || !trim($_POST["current_password"])){
      Report::error(Language::get("MISSING_CURRENT_PASS"));
    }
    
    if($count == Report::count("ERROR") && Auth::salt_password($_POST["current_password"], user["salt"]) != user["password"]){
      Report::error(Language::get("WRONG_PASSWORD"));
    }
    
    if($count == Report::count("ERROR")){
      $db = Database::get();
      $db->query("UPDATE `user` SET `password`='{$db->escape(Auth::salt_password($_POST["password"], user["salt"]))}' WHERE `id`='".user["id"]."'");
      Report::okay(Language::get("PASSWORD_UPDATED"));                                         
    }
    header("location: #");
    exit;
  }
  
  private function updateProfile(){
    $error = Report::count("ERROR");
    Language::load("auth");
    if(empty($_POST["username"]) || !trim($_POST["username"])){
      Report::error(Language::get("MISSING_USERNAME"));
    }
    
    if(empty($_POST["email"]) || !trim($_POST["email"])){
      Report::error(Language::get("MISSING_EMAIL"));
    }
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Report::error(Language::get("MISSING_CONTROLE_PASS"));
    }
    
    if($error == Report::count("ERROR")){
      if($data = Auth::controleDetail($_POST["username"], $_POST["email"])){
        Report::error(Language::get("P_DATA_TAKEN", [$data]));
      }else{
        $password = Auth::salt_password($_POST["password"], user["salt"]);
        if($password == user["password"]){
          //wee find out what there has changed.
          $extra = "";
          if(user["username"] !== $_POST["username"])
            Log::user(user["id"], "LOG_EMAIL_CHANGE", user["username"], $_POST["username"]);
          if(user["email"] !== $_POST["email"]){
            Log::user(user["id"], "LOG_EMAIL_CHANGE", user["username"], user["email"], $_POST["email"]);
            Report::error(Language::get("NEED_ACTIVATE_E"));
            $extra = ", `isActivatet`='0'";
            $this->sendReActivateEmail($_POST["email"]);
          }
          $db = Database::get();
          $db->query("UPDATE `user` SET 
                       `username`='{$db->escape($_POST["username"])}',
                       `email`='{$db->escape($_POST["email"])}'{$extra}
                      WHERE `id`='".user["id"]."'");
          Report::okay(Language::get("ACCOUNT_UPDATED"));
        }else{
          Report::error(Language::get("MISSING_CONTROLE_PASS"));
        }
      }
    }
    header("location: #");
    exit;
  }
  
  private function sendReActivateEmail(string $email){
    $e = new Email("email_change");
    $e->pushArg("username", user["username"]);
    $e->pushArg("link",     geturl()."?salt=".urlencode(user["salt"])."&email=".urlencode($email));
    $e->send($email);
  }
  
  private function activateUser(int $id){
    Database::get()->query("UPDATE `user` SET `isActivatet`='1' WHERE `id`='{$id}'");
    Report::okay(Language::get("USER_ACTIVATED"));
    Log::user($id, "LOG_OTHER_ACTIVATE", user["username"]);
    header("location: ?view=profile&user=".$_GET["user"]);
    exit;
  }
}