<?php
namespace Lib\Ext\Page\HandleTickets;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Report;
use Lib\Config;
use Lib\Plugin\Plugin;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Log;
use Lib\Cache;
use Lib\Category;
use Lib\Exception\CategoryNotFound;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "handleTickets";
  }
  
  public function access() : array{
    return [
      "CATEGORY_CREATE",
      "CATEGORY_DELETE",
      "CATEGORY_CLOSE",
    ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    $ticket_access = Access::userHasAccesses([
      "CATEGORY_APPEND",
      "CATEGORY_ITEM_DELETE",
      "CATEGORY_SETTING"
    ]);
    if(!empty($_GET["catogory"]) && $ticket_access){
      $this->setting($tempelate, $page);
    }else{
      $this->overview($tempelate, $page, $ticket_access);
    }
  }
  
  private function setting(Tempelate $tempelate, Page $page){
    $data = $this->getData();
    if(!$data){
      Report::error("Unknown catagory");
      header("location: ?view=".$_GET["view"]);
      exit;
    }
    
    if(!empty($_POST["append"]) && Access::userHasAccess("CATEGORY_APPEND")){
      $this->appendInput($data->id);
    }
    
    if(!empty($_POST["setting"]) && Access::userHasAccess("CATEGORY_SETTING")){
      $this->updateSetting($data->id);
    }
    
    if(!empty($_GET["delete"]) && Access::userHasAccess("CATEGORY_ITEM_DELETE")){
      $this->deleteInput($_GET["delete"]);
    }
    
    $query = Database::get()->query("SELECT * FROM `category_item` WHERE `cid`='{$data->id}'");
    $item = [];
    while($row = $query->fetch())
      $item[] = $row->toArray();
    $tempelate->put("item", $item);
    
    $tempelate->put("category_id", $data->id);
    $tempelate->put("age", $data->age);
    
    $tempelate->render("handle_ticket", $page);
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
  
  private function overview(Tempelate $tempelate, Page $page, bool $ticket_access){
    if(!empty($_POST["name"]) && Access::userHasAccess("CATEGORY_CREATE")){
      $this->create($_POST["name"]);
    }
    if(!empty($_GET["open"]) && Access::userHasAccess("CATEGORY_CLOSE")){
      $this->changeOpen(intval($_GET["open"]));
    }
    if(!empty($_GET["delete"]) && Access::userHasAccess("CATEGORY_DELETE")){
      $this->delete(intval($_GET["delete"]));
    }
    
    $query = Database::get()->query("SELECT * FROM `catogory`");
    $cat = [];
    while($row = $query->fetch())
      $cat[] = $row->toArray();
    $tempelate->put("categorys", $cat);
    
    $tempelate->put("ticket_access", $ticket_access);
    
    $tempelate->render("handle_tickets", $page);
  }
  
  private function changeOpen(int $id){
    $db = Database::get();
    $data = $db->query("SELECT `open`, `name` FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if(!$data){
      return;
    }
    
    $db->query("UPDATE `catogory` SET `open`='".($data->open == 1 ? '0' : '1')."' WHERE `id`='{$id}'");
    if($data->open == 1){
      Config::set("cat_open", Config::get("cat_open")-1);
      Report::okay("The category is now closed");
      Log::system("%s closed the category %s", user["username"], $data->name);
    }else{
      Report::okay("The category is now open");
      Log::system("%s opnede the category %s", user["username"], $data->name);
      Config::set("cat_open", Config::get("cat_open")+1);
    }
    
    header("location: ?view=".$_GET["view"]);
    exit;
  }
  
  private function delete(int $id){
    try{
      Category::delete($id);
      Report::okay("The category is now deleted");
    }catch(CategoryNotFound $e){
      Report::error("Category not found");
    }
  }
  
  private function create(string $name){
    if(in_array($name, Category::getNames())){
      Report::error("There exists a category width that names allready.");
      return;
    }
    Category::create($name);
    Report::okay("Category is created");
    Log::system("%s created a new category '%s'", user["username"], $name);
    if(Cache::exists("category_names"))
      Cache::delete("category_names");
    header("location: #");
    exit;
  }
}