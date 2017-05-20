<?php
namespace Lib\Ext\Page\Profile;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\User\Auth;
use Lib\Age;

class PageView implements P{
  public function body(){
    $user = $this->getUser();
    if(!$user){
      html_error("Could not find the user");
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
      echo "<h3>Your profile</h3>";
    }else{
      echo "<h3>Profile for ".htmlentities($user->username)."</h3>";
    }
    
    $table = new \Table();
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
      $table = new \Table();
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
      
      $table = new \Table();
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
    }
  }
  
  private function getUser(){
    $db = Database::get();
    return $db->query("SELECT * FROM `user` WHERE `id`='{$db->escape(!empty($_GET['user']) ? $_GET["user"] : user["id"])}'")->fetch();
  }
  
  private function updateAge(){
    $error = html_error_count();
    
    if(empty($_POST["day"]) || !trim($_POST["day"]) || !is_numeric($_POST["day"])){
      html_error("Missing birth day");
    }
    
    if(empty($_POST["month"]) || !trim($_POST["month"]) || !is_numeric($_POST["month"])){
      html_error("Missing month");
    }
    
    if(empty($_POST["year"]) || !trim($_POST["year"]) || !is_numeric($_POST["year"])){
      html_error("Missing year");
    }
    
    if($error == html_error_count()){
      $db = Database::get();
      $db->query("UPDATE `user` SET 
                  `birth_day`   = '{$db->escape($_POST["day"])}',
                  `birth_month` = '{$db->escape($_POST["month"])}',
                  `birth_year`  = '{$db->escape($_POST["year"])}'
                 WHERE `id`='".user["id"]."'");
      html_okay("Bith data is now updated");
    }
    
    header("location: #");
    exit;
  }
  
  private function updatePass(){
    $count = html_error_count();
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      html_error("Missing password");
    }
    
    if(empty($_POST["repeat_password"]) || !trim($_POST["password"])){
      html_error("Missing repeat password");
    }
    
    if($count == html_error_count() && $_POST["password"] != $_POST["repeat_password"]){
      html_error("They two password is not equel");
    }
    
    if(empty($_POST["current_password"]) || !trim($_POST["current_password"])){
      html_error("Missing your current password");
    }
    
    if($count == html_error_count() && salt_password($_POST["current_password"], user["salt"]) != user["password"]){
      html_error("Wrong current passowrd");
    }
    
    if($count == html_error_count()){
      $db = Database::get();
      $db->query("UPDATE `user` SET `password`='{$db->escape(salt_password($_POST["password"], user["salt"]))}' WHERE `id`='".user["id"]."'");
      html_okay("Password is now updated");                                         
    }
    header("location: #");
    exit;
  }
  
  private function updateProfile(){
    $error = html_error_count();
    
    if(empty($_POST["username"]) || !trim($_POST["username"])){
      html_error("Missing username");
    }
    
    if(empty($_POST["email"]) || !trim($_POST["email"])){
      html_error("Missing email");
    }
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      html_error("Missing controle password");
    }
    
    if($error == html_error_count()){
      if($data = Auth::controleDetail($_POST["username"], $_POST["email"])){
        html_error($data." is taken");
      }else{
        $password = salt_password($_POST["password"], user["salt"]);
        if($password == user["password"]){
          $db = Database::get();
          $db->query("UPDATE `user` SET 
                       `username`='{$db->escape($_POST["username"])}',
                       `email`='{$db->escape($_POST["email"])}'
                      WHERE `id`='".user["id"]."'");
          html_okay("Your profile is now updated");
        }else{
          html_error("Controle password was wrong");
        }
      }
    }
    header("location: #");
    exit;
  }
}
