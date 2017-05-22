<?php
namespace Lib\Ext\Page\Profile;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\User\Auth;
use Lib\Age;
use Lib\Error;
use Lib\Html\Table;
use Lib\Okay;

class PageView implements P{
  public function body(){
    $user = $this->getUser();
    if(!$user){
      Error::report("Could not find the user");
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
    }
  }
  
  private function getUser(){
    $db = Database::get();
    return $db->query("SELECT * FROM `user` WHERE `id`='{$db->escape(!empty($_GET['user']) ? $_GET["user"] : user["id"])}'")->fetch();
  }
  
  private function updateAge(){
    $error = Error::count();
    
    if(empty($_POST["day"]) || !trim($_POST["day"]) || !is_numeric($_POST["day"])){
      Error::report("Missing birth day");
    }
    
    if(empty($_POST["month"]) || !trim($_POST["month"]) || !is_numeric($_POST["month"])){
       Error::report("Missing month");
    }
    
    if(empty($_POST["year"]) || !trim($_POST["year"]) || !is_numeric($_POST["year"])){
       Error::report("Missing year");
    }
    
    if($error == Error::count()){
      $db = Database::get();
      $db->query("UPDATE `user` SET 
                  `birth_day`   = '{$db->escape($_POST["day"])}',
                  `birth_month` = '{$db->escape($_POST["month"])}',
                  `birth_year`  = '{$db->escape($_POST["year"])}'
                 WHERE `id`='".user["id"]."'");
      Okay::report("Bith data is now updated");
    }
    
    header("location: #");
    exit;
  }
  
  private function updatePass(){
    $count = Error::count();
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Error::report("Missing password");
    }
    
    if(empty($_POST["repeat_password"]) || !trim($_POST["password"])){
      Error::report("Missing repeat password");
    }
    
    if($count == Error::count() && $_POST["password"] != $_POST["repeat_password"]){
      Error::report("They two password is not equel");
    }
    
    if(empty($_POST["current_password"]) || !trim($_POST["current_password"])){
      Error::report("Missing your current password");
    }
    
    if($count == Error::count() && Auth::salt_password($_POST["current_password"], user["salt"]) != user["password"]){
      Error::report("Wrong current passowrd");
    }
    
    if($count == Error::count()){
      $db = Database::get();
      $db->query("UPDATE `user` SET `password`='{$db->escape(Auth::salt_password($_POST["password"], user["salt"]))}' WHERE `id`='".user["id"]."'");
      Okay::report("Password is now updated");                                         
    }
    header("location: #");
    exit;
  }
  
  private function updateProfile(){
    $error = Error::count();
    
    if(empty($_POST["username"]) || !trim($_POST["username"])){
      Error::report("Missing username");
    }
    
    if(empty($_POST["email"]) || !trim($_POST["email"])){
      Error::report("Missing email");
    }
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      Error::report("Missing controle password");
    }
    
    if($error == Error::count()){
      if($data = Auth::controleDetail($_POST["username"], $_POST["email"])){
        Error::report($data." is taken");
      }else{
        $password = Auth::salt_password($_POST["password"], user["salt"]);
        if($password == user["password"]){
          $db = Database::get();
          $db->query("UPDATE `user` SET 
                       `username`='{$db->escape($_POST["username"])}',
                       `email`='{$db->escape($_POST["email"])}'
                      WHERE `id`='".user["id"]."'");
          Okay::report("Your profile is now updated");
        }else{
          Error::report("Controle password was wrong");
        }
      }
    }
    header("location: #");
    exit;
  }
}