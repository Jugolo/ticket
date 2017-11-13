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

class PageView implements P{
  public function body(Tempelate $tempelate){
    $user = $this->getUser();
    if(!$user){
      Report::error("Could not find the user");
      notfound();
      return;
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
        if(group["activateUser"] == 1 && !empty($_GET["activate"])){
          $this->activateUser($user->id);
        }
        $tempelate->put("not_activate", true);
      }
      if(group["viewUserLog"] == 1){
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
    return;
    
    $table = new Table();
    $table->style = "width:100%;border-collapse:collapse;";
    $table->newColummen();
    if($user->id == user["id"]){
      $table->th("Username")->style = "border:1px solid grey;";
      $table->td("<input type='text' name='username' value='".htmlentities($user->username)."'>", true)->style = "border:1px solid grey";
      $table->newColummen();
      $table->th("Email")->style = "border:1px solid grey;";
      $table->td("<input type='email' name='email' value='".htmlentities($user->email)."'>", true)->style = "border:1px solid grey;";
      $table->newColummen();
      $table->th("Controle password")->style = "border:1px solid grey;";
      $table->td("<input type='password' name='password'>", true)->style = "border:1px solid grey";
      $table->newColummen();
      $element = $table->td("<input type='submit' name='update' value='Update the setting'>", true);
      $element->colspan = "2";
      $element->style = "border:1px solid black;";
      echo "<form method='post' action='#'>";
        $table->output();
      echo "</form>";
      
      echo "<h3>Change you password</h3>";
      $table = new Table();
      $table->style = "border-collapse:collapse;width:100%;";
      $table->newColummen();
      $table->th("Password")->style = "border:1px solid grey;";
      $table->td("<input type='password' name='password'>", true)->style = "border: 1px solid grey;";
      $table->newColummen();
      $table->th("Repeat password")->style = "border:1px solid grey";
      $table->td("<input type='password' name='repeat_password'>", true)->style = "border: 1px solid grey;";
      $table->newColummen();
      $table->th('Current password')->style = "border:1px solid grey;";
      $table->td("<input type='password' name='current_password'>", true)->style = "border:1px solid grey;";
      $table->newColummen();
      $row = $table->td("<input type='submit' name='pass' value='Change password'>", true);
      $row->colspan = "2";
      $row->style = "border:1px solid grey";
      
      echo "<form method='post' action='#'>";
        $table->output();
      echo "</form>";
      
      $age = "";
      if(user["birth_day"] && user["birth_month"] && user["birth_year"]){
        $age = Age::calculate(user["birth_day"], user["birth_month"], user["birth_year"]);
      }
      
      echo "<h3>Age.".($age ? " (".$age.")" : "")."</h3>";
      
      $table = new Table();
      $table->style = "border-collapse:collapse;width:100%;";
      $table->newColummen();
      $table->th("Birth day")->style = "border: 1px solid grey;";
      $table->td("<input type='number' name='day' value='".user["birth_day"]."'>", true)->style = "border: 1px solid grey;";
      $table->newColummen();
      $table->th("Birth month")->style = "border:1px solid grey";
      $table->td("<input type='number' name='month' value='".user["birth_month"]."'>", true)->style = "border:1px solid grey";
      $table->newColummen();
      $table->th("Birth year")->style = "border:1px solid grey";
      $table->td("<input type='number' name='year' value='".user["birth_year"]."'>", true)->style = "border: 1px solid grey";
      $table->newColummen();
      $row = $table->td("<input type='submit' name='birth' value='Update'>", true);
      $row->colspan = "2";
      $row->style = "border:1px solid grey";
      echo "<form method='POST' action='#'>";
        $table->output();
      echo "</form>";
    }else{
      $table->th("Username")->style = "border:1px solid grey";
      $table->td($user->username)->style = "border:1px solid grey;";
      $table->th("Email")->style = "border:1px solid grey;";
      $table->td($user->email)->style = "border:1px solid grey;";
      $table->newColummen();
      $row = $table->th("Age");
      $row->colspan = "2";
      $row->style = "border:1px solid grey;";
      if($user->birth_day && $user->birth_month && $user->birth_year){
        $row = $table->td(Age::calculate($user->birth_day, $user->birth_month, $user->birth_year));
      }else{
        $row = $table->td("Unknown");
      }
      $row->colspan = "2";
      $row->style = "border:1px solid grey;";
      $table->output();
      if(group["viewUserLog"] == 1){
        $log = Log::getUserLog($user->id);
        if($log->size() > 0){
          echo "<fieldset id='userlog'>";
            echo "<legend>User log</legend>";
            $log->render(function($time, $message){
              echo "<div>";
                echo "<span>[{$time}]</span> ";
                echo $message;
              echo "</div>";
            });
          echo "</fieldset>";
        }
      }
    }
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
      Report::error("Missing birth day");
    }
    
    if(empty($_POST["month"]) || !trim($_POST["month"]) || !is_numeric($_POST["month"])){
       Report::error("Missing month");
    }
    
    if(empty($_POST["year"]) || !trim($_POST["year"]) || !is_numeric($_POST["year"])){
       Report::error("Missing year");
    }
    
    if($error == Report::count("ERROR")){
      $db = Database::get();
      $db->query("UPDATE `user` SET 
                  `birth_day`   = '{$db->escape($_POST["day"])}',
                  `birth_month` = '{$db->escape($_POST["month"])}',
                  `birth_year`  = '{$db->escape($_POST["year"])}'
                 WHERE `id`='".user["id"]."'");
      Report::okay("Bith data is now updated");
    }
    
    header("location: #");
    exit;
  }
  
  private function updatePass(){
    $count = Report::count("ERROR");
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Report::error("Missing password");
    }
    
    if(empty($_POST["repeat_password"]) || !trim($_POST["password"])){
      Report::error("Missing repeat password");
    }
    
    if($count == Report::count("ERROR") && $_POST["password"] != $_POST["repeat_password"]){
      Report::error("They two password is not equel");
    }
    
    if(empty($_POST["current_password"]) || !trim($_POST["current_password"])){
      Report::error("Missing your current password");
    }
    
    if($count == Report::count("ERROR") && Auth::salt_password($_POST["current_password"], user["salt"]) != user["password"]){
      Report::error("Wrong current passowrd");
    }
    
    if($count == Report::count("ERROR")){
      $db = Database::get();
      $db->query("UPDATE `user` SET `password`='{$db->escape(Auth::salt_password($_POST["password"], user["salt"]))}' WHERE `id`='".user["id"]."'");
      Report::okay("Password is now updated");                                         
    }
    header("location: #");
    exit;
  }
  
  private function updateProfile(){
    $error = Report::count("ERROR");
    
    if(empty($_POST["username"]) || !trim($_POST["username"])){
      Report::error("Missing username");
    }
    
    if(empty($_POST["email"]) || !trim($_POST["email"])){
      Report::error("Missing email");
    }
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Report::error("Missing controle password");
    }
    
    if($error == Report::count("ERROR")){
      if($data = Auth::controleDetail($_POST["username"], $_POST["email"])){
        Report::error($data." is taken");
      }else{
        $password = Auth::salt_password($_POST["password"], user["salt"]);
        if($password == user["password"]){
          //wee find out what there has changed.
          $extra = "";
          if(user["username"] !== $_POST["username"])
            Log::user(user["id"], "%s changed nick to %s", user["username"], $_POST["username"]);
          if(user["email"] !== $_POST["email"]){
            Log::user(user["id"], "%s changed the email from %s to %s", user["username"], user["email"], $_POST["email"]);
            Report::error("Becuse you have changed you email you need to activate the account again");
            $extra = ", `isActivatet`='0'";
            $this->sendReActivateEmail($_POST["email"]);
          }
          $db = Database::get();
          $db->query("UPDATE `user` SET 
                       `username`='{$db->escape($_POST["username"])}',
                       `email`='{$db->escape($_POST["email"])}'{$extra}
                      WHERE `id`='".user["id"]."'");
          Report::okay("Your profile is now updated");
        }else{
          Report::error("Controle password was wrong");
        }
      }
    }
    header("location: #");
    exit;
  }
  
  private function sendReActivateEmail(string $email){
    $e = new Email();
    $e->pushArg("username", user["username"]);
    $e->pushArg("link",     geturl()."?salt=".urlencode(user["salt"])."&email=".urlencode($email));
    $e->send("email_change", $email);
  }
  
  private function activateUser(int $id){
    Database::get()->query("UPDATE `user` SET `isActivatet`='1' WHERE `id`='{$id}'");
    Report::okay("The user is now activated and can log in");
    Log::user($id, "%s activated the user", user["username"]);
    header("location: ?view=profile&user=".$_GET["user"]);
    exit;
  }
}