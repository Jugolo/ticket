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
use Lib\Language\Language;

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
      "CATEGORY_SORT",
    ];
  }
  
  public function body(Tempelate $tempelate, Page $page){
    Language::load("admin_ticket");
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
      Report::error(Language::get("UNKNOWN_CAT"));
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
      $this->deleteInput($_GET["delete"], $data);
    }
    
    $query = Database::get()->query("SELECT * FROM `category_item` WHERE `cid`='{$data->id}'");
    $item = [];
    while($row = $query->fetch())
      $item[] = $row->toArray();
    $tempelate->put("item", $item);
    
    $tempelate->put("category_id", $data->id);
    $tempelate->put("age", $data->age);
    
    $tempelate->render("handle_ticket");
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
    Report::okay(Language::get("SETTING_UPDATED"));
    header("location: #");
    exit;
  }
  
  public function deleteInput(int $id, $data){
    $db = Database::get();
    $db->query("DELETE FROM `category_item` WHERE `id`='{$id}'");
    $extra = "";
    if($data->open === 1 && $data->input_count-1 <= 0){
      $extra = ", `open`='0'";
    }
    $db->query("UPDATE `catogory` SET `input_count`=input_count-1{$extra} WHERE `id`='{$data->id}'");
    Report::okay(Language::get("INPUT_DELETED"));
    header("location: ?view={$_GET["view"]}&catogory=".$_GET["catogory"]);
    exit;
  }
  
  public function appendInput(int $id){
    $error_count = Report::count("ERROR");
    if(empty($_POST["name"]) || !trim($_POST["name"])){
      Report::error(Language::get("MISSING_I_NAME"));
    }
    
    if(empty($_POST["type"]) || $_POST["type"] < 0 || $_POST["type"] > 3){
      Report::error(Language::get("MISSING_I_TYPE"));
    }
    
    if(empty($_POST["placeholder"]) || !trim($_POST["placeholder"])){
      Report::error(Language::get("MISSING_PLACEHOLDER"));
    }
    
    if($error_count == Report::count("ERROR")){
      $db = Database::get();
      $db->query("UPDATE `catogory` SET `input_count`=input_count+1 WHERE `id`='{$id}'");
      $db->query("INSERT INTO `category_item` VALUES (
                   NULL,
                   '{$id}',
                   '{$db->escape($_POST["type"])}',
                   '{$db->escape($_POST["name"])}',
                   '{$db->escape($_POST["placeholder"])}'
                 );");
      Report::okay(Language::get("INPUT_SAVED"));
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
      $this->create($_POST["name"], $ticket_access);
    }
    if(!empty($_GET["open"]) && Access::userHasAccess("CATEGORY_CLOSE")){
      $this->changeOpen(intval($_GET["open"]));
    }
    if(!empty($_GET["delete"]) && Access::userHasAccess("CATEGORY_DELETE")){
      $this->delete(intval($_GET["delete"]), $tempelate, $page);
    }
    
    if(Access::userHasAccess("CATEGORY_SORT")){
      if(!empty($_GET["up"])){
        $this->moveUp(intval($_GET["up"]));
      }elseif(!empty($_GET["down"])){
        $this->moveDown($_GET["down"]);
      }
    }
    
    $query = Database::get()->query("SELECT * FROM `catogory` ORDER BY `sort_ordre` ASC");
    $cat = [];
    while($row = $query->fetch())
      $cat[] = $row->toArray();
    $tempelate->put("categorys", $cat);
    $tempelate->put("last_sort", count($cat)-1);
    
    $tempelate->put("ticket_access", $ticket_access);
    
    $tempelate->render("handle_tickets");
  }
  
  private function changeOpen(int $id){
    $db = Database::get();
    $data = $db->query("SELECT `open`, `name`, `input_count` FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if(!$data){
      return;
    }
    
    if($data->input_count > 0){
      $db->query("UPDATE `catogory` SET `open`='".($data->open == 1 ? '0' : '1')."' WHERE `id`='{$id}'");
      if($data->open == 1){
        Config::set("cat_open", Config::get("cat_open")-1);
        Report::okay(Language::get("CAT_CLOSED"));
        Log::system("LOG_CAT_CLOSE", user["username"], $data->name);
      }else{
        Report::okay(Language::get("CAT_OPNED"));
        Log::system("LOG_CAT_OPEN", user["username"], $data->name);
        Config::set("cat_open", Config::get("cat_open")+1);
      }
    }else{
      Report::error(Language::get("CANT_OPEN_INPUT"));
    }
    
    header("location: ?view=".$_GET["view"]);
    exit;
  }
  
  private function delete(int $id, Tempelate $tempelate, Page $page){
    //first wee get the count of ticket in this category
    $data = Database::get()->query("SELECT * FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if($data->ticket_count > 0){
      $this->selectTicketMove($data, $tempelate, $page);
    }
    try{
      if(Category::delete($id))
        Report::okay(Language::get("CAT_DELETED"));
    }catch(CategoryNotFound $e){
      Report::error(Language::get("UNKNOWN_CAT"));
    }
  }
  
  private function selectTicketMove($data, Tempelate $tempelate, Page $page){
    $select = [["to" => "delete", "name" => Language::get("DELETE")]];
    $to = empty($_POST["ticket_option"]) || !trim($_POST["ticket_option"]) ? "none" : $_POST["ticket_option"];
    if($to == "delete")
      return;//the category deleter function will delete all tickets in the category
    
    $db = Database::get();
    $query = $db->query("SELECT `id`, `name` FROM `catogory` WHERE `id`<>'{$data->id}'");
    while($row = $query->fetch()){
      if($to == $row->id){
        $db->query("UPDATE `ticket` SET `cid`='{$row->id}' WHERE `cid`='{$data->id}'");
        $db->query("UPDATE `catogory` SET `ticket_count`=ticket_count+{$data->ticket_count} WHERE `id`='{$row->id}'");
        return;
      }
      $select[] = ["to" => $row->id, "name" => $row->name];
    }
    
    if($to != "nonne"){
      Report::error(Language::get("UNKNOWN_CAT"));
    }
    
    $tempelate->put("options", $select);
    $tempelate->render("delete_ticket");
    exit;
  }
  
  private function create(string $name, bool $access){
    if(in_array($name, Category::getNames())){
      Report::error(Language::get("CAT_NAME_EXISTS"));
      return;
    }
    $id = Category::create($name);
    Report::okay(Language::get("CAT_CREATED"));
    Log::system("LOG_NEW_CAT", user["username"], $name);
    if(Cache::exists("category_names"))
      Cache::delete("category_names");
    if($access)
      header("location: ?view=handleTickets&catogory=".$id);
    else
      header("location: #");
    exit;
  }
  
  public function moveUp(int $id){
    $db = Database::get();
    $data = $db->query("SELECT `sort_ordre` FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if($data->sort_ordre == 0){
      Report::error(Language::get("CANT_UP"));
      return;
    }
    $db->query("UPDATE `catogory` SET `sort_ordre`=sort_ordre+1 WHERE `sort_ordre`='".($data->sort_ordre-1)."'");
    $db->query("UPDATE `catogory` SET `sort_ordre`=sort_ordre-1 WHERE `id`='{$id}'");
    Report::okay(Language::get("SORT_O_O"));
  }
  
  public function moveDown(int $id){
    //two stop eventuel error in our sort system wee need to finde the heigst sort score.
    $db = Database::get();
    $top = $db->query("SELECT COUNT(`id`) AS id FROM `catogory`")->fetch()->id-1;
    $data = $db->query("SELECT `sort_ordre` FROM `catogory` WHERE `id`='{$id}'")->fetch();
    if($data->sort_ordre == $top){
      Report::error(Language::get("CANT_DOWN"));
      return;
    }
    
    $db->query("UPDATE `catogory` SET `sort_ordre`=sort_ordre-1 WHERE `sort_ordre`='".($data->sort_ordre+1)."'");
    $db->query("UPDATE `catogory` SET `sort_ordre`=sort_ordre+1 WHERE `id`='{$id}'");
    Report::okay(Language::get("SORT_D_O"));
  }
}