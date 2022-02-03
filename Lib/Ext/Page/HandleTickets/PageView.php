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
use Lib\Log;
use Lib\Cache;
use Lib\Category;
use Lib\Exception\CategoryNotFound;
use Lib\File\FileExtension;
use Lib\User\User;
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
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    Language::load("admin_ticket");
    $ticket_access = $user->access()->hasMuliAccess([
      "CATEGORY_APPEND",
      "CATEGORY_ITEM_DELETE",
      "CATEGORY_SETTING"
    ]);
    if(!empty($_GET["catogory"]) && $ticket_access){
      $this->setting($tempelate, $page, $user);
    }else{
      $this->overview($tempelate, $page, $ticket_access, $user);
    }
  }
  
  private function setting(Tempelate $tempelate, Page $page, User $user){
    $data = $this->getData();
    if(!$data){
      Report::error(Language::get("UNKNOWN_CAT"));
      header("location: ?view=".$_GET["view"]);
      exit;
    }
    
    $access = $user->access();
    if(!empty($_GET["access"]) && $access->has("CATEGORY_ACCESS"))
		$this->handleAccess($tempelate, $data->toArray());
    
    if(!empty($_POST["append"]) && $access->has("CATEGORY_APPEND")){
      $this->appendInput($data->id);
    }
    
    if(!empty($_POST["setting"]) && $access->has("CATEGORY_SETTING")){
      $this->updateSetting($data->id);
    }
    
    if(!empty($_GET["delete"]) && $access->has("CATEGORY_ITEM_DELETE")){
      $this->deleteInput($_GET["delete"], $data);
    }
    
    $extension = new FileExtension();
    $query = Database::get()->query("SELECT * FROM `".DB_PREFIX."category_item` WHERE `cid`='{$data->id}'");
    $item = [];
    while($row = $query->fetch()){
      $d = $row->toArray();
      if($row->type == 4)
        $d["placeholder"] = $extension->getGroupName((int)$row->placeholder);
      $item[] = $d;
    }
    $tempelate->put("item", $item);
    
    $item = [];
    foreach($extension->getGroup() as $ex)
      $item[] = [
        "id"   => $ex->getID(),
        "name" => $ex->getName()
      ];
    $tempelate->put("file_extension", $item);
    
    $tempelate->put("category_id", $data->id);
    $tempelate->put("age", $data->age);
    
    $tempelate->put("nav", [
        [
           "txt"  => Language::get("TICKET"),
           "link" => "?view=handleTickets"
        ],
        [
           "txt"  => Language::get("SETTING"),
           "link" => "#"
        ]
    ]);
    
    $tempelate->render("handle_ticket");
  }
  
  private function getGroup(int $gid){
	   return Database::get()->query("SELECT * FROM `".DB_PREFIX."group` WHERE `id`='".$gid."'")->fetch();
  }
  
  private function getAccess(int $cid, int $gid) : array{
	  $query = Database::get()->query("SELECT `name` FROM `".DB_PREFIX."category_access` WHERE `cid`='{$cid}' AND `gid`='{$gid}'");
	  $list = [];
	  while($row = $query->fetch())
		$list[] = $row->name;
	  return $list;
  }
  
  private function handleAccess(Tempelate $tempelate, array $catData){
	  $group = $_GET["access"];
	  if($group == "null" || !($data = $this->getGroup((int)$group))){
		  $this->selectGroup($tempelate, $catData);
	  }
	  
	  $gaccess = $this->getAccess($catData["id"], $data->id);
	  
	  if(!empty($_POST["update"])){
		  $insert = [];
		  $delete = [];
		  
		  if(!empty($_POST["TICKET_OTHER"]) && !in_array("TICKET_OTHER", $gaccess))
			$insert[] = "TICKET_OTHER";
		  elseif(empty($_POST["TICKET_OTHER"]) && in_array("TICKET_OTHER", $gaccess))
		    $delete[] = "TICKET_OTHER";
		    
		  if(!empty($_POST["TICKET_DELETE"]) && !in_array("TICKET_DELETE", $gaccess))
			$insert[] = "TICKET_DELETE";
		  elseif(empty($_POST["TICKET_DELETE"]) && in_array("TICKET_DELETE", $gaccess))
		    $delete[] = "TICKET_DELETE";
		    
		  if(!empty($_POST["COMMENT_DELETE"]) && !in_array("COMMENT_DELETE", $gaccess))
			$insert[] = "COMMENT_DELETE";
		  elseif(empty($_POST["COMMENT_DELETE"]) && in_array("COMMENT_DELETE", $gaccess))
		    $delete[] = "COMMENT_DELETE";
		    
		  if(!empty($_POST["TICKET_LOG"]) && !in_array("TICKET_LOG", $gaccess))
			$insert[] = "TICKET_LOG";
		  elseif(empty($_POST["TICKET_LOG"]) && in_array("TICKET_LOG", $gaccess))
		    $delete[] = "TICKET_LOG";
		    
		  if(!empty($_POST["TICKET_SEEN"]) && !in_array("TICKET_SEEN", $gaccess))
			$insert[] = "TICKET_SEEN";
		  elseif(empty($_POST["TICKET_SEEN"]) && in_array("TICKET_SEEN", $gaccess))
		    $delete[] = "TICKET_SEEN";
		    
		  if(!empty($_POST["APPLY_CAT"]) && !in_array("APPLY_CAT", $gaccess))
			$insert[] = "APPLY_CAT";
		  elseif(empty($_POST["APPLY_CAT"]) && in_array("APPLY_CAT", $gaccess))
		    $delete[] = "APPLY_CAT";
		    
		  if(!empty($_POST["TICKET_CLOSE"]) && !in_array("TICKET_CLOSE", $gaccess))
			$insert[] = "TICKET_CLOSE";
		  elseif(empty($_POST["TICKET_CLOSE"]) && in_array("TICKET_CLOSE", $gaccess))
		    $delete[] = "TICKET_CLOSE";  
		    
		  if(count($insert) == 0 && count($delete) == 0){
			  Report::error(Language::get("NO_UPDATE"));
			  header("location: #");
			  exit;
		  }
		  
		  if(count($insert) > 0){
			  $sql = "INSERT INTO `".DB_PREFIX."category_access` (`gid`, `cid`, `name`) VALUES";
			  for($i=0;$i<count($insert);$i++){
				  $sql .= ($i == 0 ? " " : ", ")."('{$data->id}', '{$catData["id"]}', '{$insert[$i]}')";
			  }
			  Database::get()->query($sql.";");
		  }
		  
		  if(count($delete) > 0){
			  $sql = "DELETE FROM `".DB_PREFIX."category_access` WHERE `gid`='{$data->id}' AND `cid`='{$catData["id"]}' AND (";
			  for($i=0;$i<count($delete);$i++){
				  $sql .= ($i == 0 ? " " : " OR ")."`name`='{$delete[$i]}'";
			  }
			  Database::get()->query($sql.")");
		  }
		  
		  Report::okay(Language::get("ACCESS_OPDATED"));
	      header("location: #");
	      exit;
	  }
      
      $tempelate->put("a_apply_cat",      in_array("APPLY_CAT", $gaccess));
      $tempelate->put("a_close_ticket",   in_array("TICKET_CLOSE", $gaccess));
      $tempelate->put("a_show_other",     in_array("TICKET_OTHER", $gaccess));
      $tempelate->put("a_delete_ticket",  in_array("TICKET_DELETE", $gaccess));
      $tempelate->put("a_delete_comment", in_array("COMMENT_DELETE", $gaccess));
      $tempelate->put("a_ticket_log",     in_array("TICKET_LOG", $gaccess));
      $tempelate->put("a_ticket_seen",    in_array("TICKET_SEEN", $gaccess));
      
      $tempelate->put("g_name", $data->name);
	  $tempelate->put("nav", [
        [
           "txt"  => Language::get("TICKET"),
           "link" => "?view=handleTickets"
        ],
        [
           "txt"  => Language::get("SETTING"),
           "link" => "?view=handleTickets&catogory=".$catData["id"]
        ],
        [
           "txt"  => Language::get("SELECT_GROUP"),
           "link" => "?view=handleTickets&catogory=".$catData["id"]."&access=null"
        ],
        [
           "txt"  => Language::get("ACCESS"),
           "link" => "#"
        ]
      ]);  
	  $tempelate->render("ticket_access");
  }
  
  private function selectGroup(Tempelate $tempelate, array $data){
	  $db = Database::get();
	  
	  $query = $db->query("SELECT `id`, `name` FROM `".DB_PREFIX."group`");
	  $list = [];
	  while($row = $query->fetch()){
		  $list[] = [$row->id, $row->name];
	  }
	  
	  $tempelate->put("catid", $data["id"]);
	  $tempelate->put("group_list", $list);
	  $tempelate->put("nav", [
        [
           "txt"  => Language::get("TICKET"),
           "link" => "?view=handleTickets"
        ],
        [
           "txt"  => Language::get("SETTING"),
           "link" => "?view=handleTickets&catogory=".$data["id"]
        ],
        [
           "txt"  => Language::get("SELECT_GROUP"),
           "link" => "#"
        ]   
      ]);
	  $tempelate->render("select_group");
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
    Database::get()->query("UPDATE `".DB_PREFIX."catogory` SET ".implode(", ", $buffer)." WHERE `id`='{$id}'");
    Report::okay(Language::get("SETTING_UPDATED"));
    header("location: #");
    exit;
  }
  
  public function deleteInput(int $id, $data){
    $db = Database::get();
    $db->query("DELETE FROM `".DB_PREFIX."category_item` WHERE `id`='{$id}'");
    $extra = "";
    if($data->open === 1 && $data->input_count-1 <= 0){
      $extra = ", `open`='0'";
    }
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `input_count`=input_count-1{$extra} WHERE `id`='{$data->id}'");
    Report::okay(Language::get("INPUT_DELETED"));
    header("location: ?view={$_GET["view"]}&catogory=".$_GET["catogory"]);
    exit;
  }
  
  public function appendInput(int $id){
    $error_count = Report::count("ERROR");
    if(empty($_POST["name"]) || !trim($_POST["name"])){
      Report::error(Language::get("MISSING_I_NAME"));
    }
    
    if(empty($_POST["type"]) || $_POST["type"] < 0 || $_POST["type"] > 4){
      Report::error(Language::get("MISSING_I_TYPE"));
    }
    
    if(empty($_POST["type"]) || $_POST["type"] != 4){
      if(empty($_POST["placeholder"]) || !trim($_POST["placeholder"])){
        Report::error(Language::get("MISSING_PLACEHOLDER"));
      }
    }elseif(!empty($_POST["type"]) && $_POST["type"] == 4){
      if(empty($_POST["file_type"]) || !trim($_POST["file_type"]))
        Report::error(Language::get("MISSING_FILE_TYPE"));
    }
    
    if($error_count == Report::count("ERROR")){
      $db = Database::get();
      $db->query("UPDATE `".DB_PREFIX."catogory` SET `input_count`=input_count+1 WHERE `id`='{$id}'");
      $db->query("INSERT INTO `".DB_PREFIX."category_item` VALUES (
                   NULL,
                   '{$id}',
                   '{$db->escape($_POST["type"])}',
                   '{$db->escape($_POST["name"])}',
                   '{$db->escape($_POST["type"] == 4 ? $_POST["file_type"] : $_POST["placeholder"])}'
                 );");
      Report::okay(Language::get("INPUT_SAVED"));
    }
    header("location: #");
    exit;
  }
  
  private function getData(){
    $db = Database::get();
    return $db->query("SELECT * FROM `".DB_PREFIX."catogory` WHERE `id`='{$db->escape($_GET["catogory"])}'")->fetch();
  }
  
  private function overview(Tempelate $tempelate, Page $page, bool $ticket_access, User $user){
	$access = $user->access();
    if(!empty($_POST["name"]) && $access->has("CATEGORY_CREATE")){
      $this->create($_POST["name"], $ticket_access, $user);
    }
    if(!empty($_GET["open"]) && $access->has("CATEGORY_CLOSE")){
      $this->changeOpen(intval($_GET["open"]), $user);
    }
    if(!empty($_GET["delete"]) && $access->has("CATEGORY_DELETE")){
      $this->delete(intval($_GET["delete"]), $tempelate, $page, $user);
    }
    
    if($access->has("CATEGORY_SORT")){
      if(!empty($_GET["up"])){
        $this->moveUp(intval($_GET["up"]));
      }elseif(!empty($_GET["down"])){
        $this->moveDown($_GET["down"]);
      }
    }
    
    $query = Database::get()->query("SELECT * FROM `".DB_PREFIX."catogory` ORDER BY `sort_ordre` ASC");
    $cat = [];
    while($row = $query->fetch())
      $cat[] = $row->toArray();
    $tempelate->put("categorys", $cat);
    $tempelate->put("last_sort", count($cat)-1);
    
    $tempelate->put("ticket_access", $ticket_access);
    $tempelate->put("nav", [
        [
           "txt" => Language::get("TICKET")
        ]
    ]);
    
    $tempelate->render("handle_tickets");
  }
  
  private function changeOpen(int $id, User $user){
    $db = Database::get();
    $data = $db->query("SELECT `open`, `name`, `input_count` FROM `".DB_PREFIX."catogory` WHERE `id`='{$id}'")->fetch();
    if(!$data){
      return;
    }
    
    if($data->input_count > 0){
      $db->query("UPDATE `".DB_PREFIX."catogory` SET `open`='".($data->open == 1 ? '0' : '1')."' WHERE `id`='{$id}'");
      if($data->open == 1){
        Config::set("cat_open", (int)Config::get("cat_open")-1);
        Report::okay(Language::get("CAT_CLOSED"));
        Log::system("LOG_CAT_CLOSE", $user->username(), $data->name);
      }else{
        Report::okay(Language::get("CAT_OPNED"));
        Log::system("LOG_CAT_OPEN", $user->username(), $data->name);
        Config::set("cat_open", (int)Config::get("cat_open")+1);
      }
    }else{
      Report::error(Language::get("CANT_OPEN_INPUT"));                 
    }
    
    header("location: ?view=".$_GET["view"]);
    exit;
  }
  
  private function delete(int $id, Tempelate $tempelate, Page $page, User $user){
    //first wee get the count of ticket in this category
    $data = Database::get()->query("SELECT * FROM `".DB_PREFIX."catogory` WHERE `id`='{$id}'")->fetch();
    if($data->ticket_count > 0){
      $this->selectTicketMove($data, $tempelate, $page);
    }
    try{
      if(Category::delete($id)){
        Report::okay(Language::get("CAT_DELETED"));
		Log::system("LOG_CAT_DELETED", $user->username(), $data->name);
	  }
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
    $query = $db->query("SELECT `id`, `name` FROM `".DB_PREFIX."catogory` WHERE `id`<>'{$data->id}'");
    while($row = $query->fetch()){
      if($to == $row->id){
        $db->query("UPDATE `".DB_PREFIX."ticket` SET `cid`='{$row->id}' WHERE `cid`='{$data->id}'");
        $db->query("UPDATE `".DB_PREFIX."catogory` SET `ticket_count`=ticket_count+{$data->ticket_count} WHERE `id`='{$row->id}'");
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
  
  private function create(string $name, bool $access, User $user){
    if(in_array($name, Category::getNames())){
      Report::error(Language::get("CAT_NAME_EXISTS"));
      return;
    }
    $id = Category::create($name);
    Report::okay(Language::get("CAT_CREATED"));
    Log::system("LOG_NEW_CAT", $user->username(), $name);
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
    $data = $db->query("SELECT `sort_ordre` FROM `".DB_PREFIX."catogory` WHERE `id`='{$id}'")->fetch();
    if($data->sort_ordre == 0){
      Report::error(Language::get("CANT_UP"));
      return;
    }
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `sort_ordre`=sort_ordre+1 WHERE `sort_ordre`='".($data->sort_ordre-1)."'");
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `sort_ordre`=sort_ordre-1 WHERE `id`='{$id}'");
    Report::okay(Language::get("SORT_O_O"));
  }
  
  public function moveDown(int $id){
    //two stop eventuel error in our sort system wee need to finde the heigst sort score.
    $db = Database::get();
    $top = $db->query("SELECT COUNT(`id`) AS id FROM `".DB_PREFIX."catogory`")->fetch()->id-1;
    $data = $db->query("SELECT `sort_ordre` FROM `".DB_PREFIX."catogory` WHERE `id`='{$id}'")->fetch();
    if($data->sort_ordre == $top){
      Report::error(Language::get("CANT_DOWN"));
      return;
    }
    
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `sort_ordre`=sort_ordre-1 WHERE `sort_ordre`='".($data->sort_ordre+1)."'");
    $db->query("UPDATE `".DB_PREFIX."catogory` SET `sort_ordre`=sort_ordre+1 WHERE `id`='{$id}'");
    Report::okay(Language::get("SORT_D_O"));
  }
}
