<?php
namespace Lib\Ext\Page\Apply;

use Lib\Ext\Notification\NewTicket;
use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Error;
use Lib\Okay;
use Lib\Age;
use Lib\Email;

class PageView implements P{
  public function body(){
    if(empty($_GET["to"]) || !($data = $this->data())){
      $this->select_to();
      return;
    }
  
    if($data["age"]){
      if(($respons = Age::controle($data["age"], $data["name"])) != Age::NO_ERROR){
        switch($respons){
          case AGE::GET_AGE:
            Age::get_age($data["name"]);
            return;
          default:
            header("location: ?view=apply");
            exit;
        }
      }
    }
  
    if(!empty($_GET["done"])){
      $this->controle_apply($data);
    }
    
    echo "<form method='post' action='?view=apply&to=".$data["id"]."&done=true'>";
    $db = Database::get();
    $query = $db->query("SELECT * FROM `category_item` WHERE `cid`='".$data["id"]."'");
    $saver = new SaveInputs($data["id"]);
    while($row = $query->fetch()){
      if($row->type == 1){
        echo two_container($row->text, "<input type='text' name='".$row->id."' value='".htmlentities($saver->get($row->id))."' placeholder='".htmlentities($row->placeholder)."'>");
      }elseif($row->type == 2){
        echo "<div class='center'>".$row->text."</div>";
        echo "<textarea class='apply' name='".$row->id."' placeholder='".htmlentities($row->placeholder)."'>".$saver->get($row->id)."</textarea>";
      }elseif($row->type == 3){
        $item = explode(",", $row->placeholder);
        $options = "";
        $s = $saver->get($row->id);
        for($i=0;$i<count($item);$i++){
          $options .= "<option value='".$i."'".($s == $item[$i] ? " selected" : "").">".trim($item[$i])."</option>";
        }
        echo two_container($row->text, "<select name='{$row->id}'>{$options}</select>");
      }
    }
    $saver->delete();
    echo "<input type='submit' value='Submit'>";
    echo "</form>";
  }
  
  private function controle_apply(array $data){
    $db = Database::get();
    $errcount = 0;
    $query = $db->query("SELECT * FROM `category_item` WHERE `cid`='".$data["id"]."'");
    $sqlBuffer = [];
    $saver = new SaveInputs($data["id"]);
    while($row = $query->fetch()){
      if(!array_key_exists($row->id, $_POST)){
        Error::report("Missing '".htmlentities($row->text)."'");
        $errcount++;
      }elseif($row->type != 3 && !trim($_POST[$row->id])){
        Error::report("Missing '".htmlentities($row->text)."'");
        $errcount++;
      }elseif($row->type == 3){
        $count = count(($option = explode(",", $row->placeholder)))-1;
        $value = intval($_POST[$row->id]);
        if($value < 0 || $value > $count){
          Error::report("Missing '".htmlentities($row->text)."'");
          $errcount++;
        }else{
          $sqlBuffer[] = "INSERT INTO `ticket_value` (`hid`, `text`, `type`, `value`) VALUES (%%hid%%, '".$db->escape($row->text)."', '".$row->type."', '".$db->escape($option[$value])."')";
          $saver->put($row->id, $value);
        }
      }else{
        $sqlBuffer[] = "INSERT INTO `ticket_value` (`hid`, `text`, `type`, `value`) VALUES(%%hid%%, '".$db->escape($row->text)."', '".$row->type."', '".$db->escape($_POST[$row->id])."')";
        $saver->put($row->id, $_POST[$row->id]);
      }
    }
    
    if($errcount !== 0 || count($sqlBuffer) == 0){
      if(count($sqlBuffer) == 0 && $errcount == 0){
        Error::report("Could not save a empty ticket");
      }
      $saver->save();
      header("location: ?view=apply&to=".$data["id"]);
      exit;
    }else{
      $id = $db->query("INSERT INTO `ticket` (`cid`, `uid`, `comments`, `created`, `user_changed`, `admin_changed`, `open`) VALUES ('".$data["id"]."', '".user["id"]."', '0', NOW(), NOW(), NOW(), '1')");
      if($db->multi_query(str_replace("(%%hid%%,", "('".$id."',", implode(";\r\n", $sqlBuffer)))){
        Okay::report("You ticket is saved");
        NewTicket::notify($id, $data["name"]);
        $this->sendEamilToAdmin($data["name"]);
        header("location: ?view=tickets&ticket_id=".$id);
        exit;
      }else{
        //sql get wrong
        $db->query("DELETE FROM `ticket` WHERE `id`='".$id."'");
        Error::report("Sorry we could not save you application");
        header("location: ?view=apply&to=".$data["id"]);
        exit;
      }
    }
  }
  
  private function select_to(){
    if(!empty($_GET["to"])){
      notfound();
      return;
    }
    
    $query = Database::get()->query("SELECT `id`, `name`, `age` FROM `catogory` WHERE `open`='1'");
    
    $options = "";
    $count = 0;
    $lastID = 0;
    while($row = $query->fetch()){
      if($row->age && !empty(user["birth_day"])){
        if(Age::calculate(user["birth_day"], user["birth_month"], user["birth_year"]) < $row->age){
         continue; 
        }
      }
      $options .= "<option value='{$row->id}'>{$row->name}</option>";
      $lastID = $row->id;
      $count++;
    }
         
    if($count == 0){
      echo "<h3>No catgory is avarible.</h3>";
      return;   
    }
    
    if($count == 1){
      header("location: ?view=apply&to=".$lastID);
      exit;
    }
    
    echo "<form method='get' action='?view=apply'>";
    echo two_container("Select to", "<select name='to'>{$options}</select>");
    echo "<input type='hidden' name='view' value='apply'>";
    echo "<input type='submit' value='Select'>";
    echo "</form>";
  }
  
  private function data(){
    $db = Database::get();
    if($data = $db->query("SELECT * FROM `catogory` WHERE `id`='".$db->escape($_GET["to"])."'")->fetch()){
      return $data->toArray();
    }
    return null;
  }
  
  private function sendEamilToAdmin(string $catName){
    $email = new Email();
    $db = Database::get();
    $query = $db->query("SELECT user.username, user.email
                         FROM `user`
                         LEFT JOIN `group` ON user.groupid=group.id
                         WHERE group.showTicket='1'
                         AND user.id<>'".user["id"]."'");
    while($row = $query->fetch()){
      $email->pushArg("username",        $row->username);
      $email->pushArg("ticket_category", $catName);
      $email->send("new_ticket",         $row->email);
    }
  }
}
