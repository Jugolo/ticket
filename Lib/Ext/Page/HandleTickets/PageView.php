<?php
namespace Lib\Ext\Page\HandleTickets;

use Lib\Controler\Page\PageView as P;
use Lib\Html\Table;
use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Report;
use Lib\Config;
use Lib\Plugin\Plugin;
use Lib\Tempelate;

class PageView implements P{
  public function body(Tempelate $tempelate){
    if(!empty($_GET["catogory"])){
      $this->setting($tempelate);
    }else{
      $this->overview($tempelate);
    }
  }
  
  private function setting(Tempelate $tempelate){
    $data = $this->getData();
    if(!$data){
      Report::error("Unknown catagory");
      header("location: ?view=".$_GET["view"]);
      exit;
    }
    
    if(!empty($_POST["append"])){
      $this->appendInput($data->id);
    }
    
    if(!empty($_POST["setting"])){
      $this->updateSetting($data->id);
    }
    
    if(!empty($_GET["delete"])){
      $this->deleteInput($_GET["delete"]);
    }
    
    $query = Database::get()->query("SELECT * FROM `category_item` WHERE `cid`='{$data->id}'");
    $item = [];
    while($row = $query->fetch())
      $item[] = $row->toArray();
    $tempelate->put("item", $item);
    
    $tempelate->put("category_id", $data->id);
    $tempelate->put("age", $data->age);
    
    $tempelate->render("handle_ticket");
    return;    
    echo "<fieldset>";
    echo "<legend>Setting</legend>";
      echo "<form method='post' action='#'>";
        echo two_container("Min. age", "<input type='text' name='age' value='{$data->age}'>");
      echo "<input type='submit' name='setting' value='Update'>";
      echo "</form>";
    echo "</fieldset>";
  }
  
  public function updateSetting(int $id){
    $input = [];
    if(!empty($_POST["age"]) && is_numeric($_POST["age"])){
      $input["age"] = "'".(int)$_POST["age"]."'";
    }else{
      $input["age"] = "NULL";
    }
    
    $sql = "UPDATE `catogory` SET ";
    $buffer = [];
    foreach($input as $name => $value){
      $buffer[] = "`{$name}`=".$value;
    }
    Database::get()->query("UPDATE `catogory` SET ".implode(", ", $buffer)." WHERE `id`='{$id}'");
    Report::okay("Setting is updated");
    header("location: #");
    exit;
  }
  
  public function deleteInput(int $id){
    Database::get()->query("DELETE FROM `category_item` WHERE `id`='{$id}'");
    Report::okay("input is deleted");
    header("location: ?view={$_GET["view"]}&catogory=".$_GET["catogory"]);
    exit;
  }
  
  public function appendInput(int $id){
    $error_count = Report::count("ERROR");
    if(empty($_POST["name"]) || !trim($_POST["name"])){
      Report::error("Missing input name");
    }
    
    if(empty($_POST["type"]) || $_POST["type"] < 0 || $_POST["type"] > 3){
      Report::error("Missing input type");
    }
    
    if(empty($_POST["placeholder"]) || !trim($_POST["placeholder"])){
      Report::error("Missing placeholder");
    }
    
    if($error_count == Report::count("ERROR")){
      $db = Database::get();
      $db->query("INSERT INTO `category_item` VALUES (
                   NULL,
                   '{$id}',
                   '{$db->escape($_POST["type"])}',
                   '{$db->escape($_POST["name"])}',
                   '{$db->escape($_POST["placeholder"])}'
                 );");
      Report::okay("The input is saved");
    }
    header("location: #");
    exit;
  }
  
  private function getData(){
    $db = Database::get();
    return $db->query("SELECT * FROM `catogory` WHERE `id`='{$db->escape($_GET["catogory"])}'")->fetch();
  }
  
  private function overview(Tempelate $tempelate){
    if(!empty($_POST["name"])){
      $this->create($_POST["name"]);
    }
    if(!empty($_GET["open"])){
      $this->changeOpen(intval($_GET["open"]));
    }
    if(!empty($_GET["delete"])){
      $this->delete(intval($_GET["delete"]));
    }
    
    $query = Database::get()->query("SELECT * FROM `catogory`");
    $cat = [];
    while($row = $query->fetch())
      $cat[] = $row->toArray();
    $tempelate->put("categorys", $cat);
    
    $tempelate->render("handle_tickets");
  }
  
  private function changeOpen(int $id){
    $db = Database::get();
    $data = $db->query("SELECT `open` FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if(!$data){
      return;
    }
    
    $db->query("UPDATE `catogory` SET `open`='".($data->open == 1 ? '0' : '1')."' WHERE `id`='{$id}'");
    if($data->open == 1){
      Config::set("cat_open", Config::get("cat_open")-1);
      Report::okay("The category is now closed");
    }else{
      Report::okay("The category is now open");
      Config::set("cat_open", Config::get("cat_open")+1);
    }
    
    header("location: ?view=".$_GET["view"]);
    exit;
  }
  
  private function delete(int $id){
    $db = Database::get();
    $data = $db->query("SELECT * FROM `catogory` WHERE `id`='".$id."'")->fetch();
    if(!$data){
      Report::error("No catogroy found to delete");
      return;
    }
    if($data->open != 0){
     Config::set("cat_open", intval(Config::get("cat_open"))-1);
    }
    Plugin::trigger_event("system.category.delete", $data);
    Report::okay("The category is now deleted");
  }
  
  private function create(string $name){
    $db = Database::get();
    $db->query("INSERT INTO `catogory` VALUES (NULL, '{$db->escape($name)}', 0, NULL);");
    Report::okay("Category is created");
    header("location: #");
    exit;
  }
}