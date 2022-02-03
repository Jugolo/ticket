<?php
namespace Lib\Ext\Plugin\FAQ;

use Lib\Controler\Page\PageView;
use Lib\Tempelate;
use Lib\Page;
use Lib\Request;
use Lib\Database;
use Lib\Access;
use Lib\Language\Language;
use Lib\Report;
use Lib\Bbcode\Parser;
use Lib\User\User;

class PageMain implements PageView{
  private $tempelateDir = "Lib/Ext/Plugin/FAQ/Tempelate/";
  public function loginNeeded() : string{
    return "BOTH";
  }
  
  public function identify() : string{
    return "page.faq.main";
  }
  
  public function access() : array{
    return [];
  }
  
  public function body(Tempelate $tempelate, Page $page, User $user){
    $this->tempelateDir .= $tempelate->getMainName()."/Pages/";
    //exit($this->tempelateDir);
    if(!Request::isEmpty(Request::GET, "create") && $user->access()->has("FAQ_CREATE")){
      $this->create($tempelate, $page);
    }elseif(!Request::isEmpty(Request::GET, "item") && $data = $this->getItemData(Request::toInt(Request::GET, "item"))){
      $this->item($tempelate, $data, $user);
    }else{
      $db = Database::get();
      $query = $db->query("SELECT `id`, `name` FROM `".DB_PREFIX."faq`");
      $cat = [];
      while($row = $query->fetch())
        $cat[$row->id] = $row->name;
      $tempelate->put("cats", $cat);
        
      $tempelate->render("Main", $this->tempelateDir);
    }
  }
  
  private function item(Tempelate $tempelate, $data, User $user){
    if(!Request::isEmpty(Request::GET, "change") && $user->access()->has("FAQ_CREATE")){
      $this->changeItem($tempelate, $data);
    }
    if(!Request::isEmpty(Request::GET, "delete") && $user->access()->has("FAQ_CREATE")){
      $this->delete($data->id);
    }
    $tempelate->put("title", $data->name);
    $parser = new Parser($data->dec);
    $tempelate->put("dec", $parser->getHtml());
    $tempelate->put("id", $data->id);
    $tempelate->render("Item", $this->tempelateDir);
  }
  
  private function delete(int $id){
    Database::get()->query("DELETE FROM `".DB_PREFIX."faq` WHERE `id`='{$id}'");
    Report::okay(Language::get("FAQ_DELETED"));
    header("location: ?view=event&event=faq.main");
    exit;
  }
  
  private function changeItem(Tempelate $tempelate, $data){
    if(!Request::isEmpty(Request::POST, "name") && !Request::isEmpty(Request::POST, "dec")){
      $this->updateItem($data->id);
    }
    $tempelate->put("is_change", true);
    $tempelate->put("name", $data->name);
    $tempelate->put("dec", $data->dec);
    $tempelate->render("Create", $this->tempelateDir);
  }
  
  private function updateItem(int $id){
    $db = Database::get();
    $db->query("UPDATE `".DB_PREFIX."faq` SET 
                  `name`='{$db->escape(Request::toString(Request::POST, "name"))}',
                  `dec`='{$db->escape(Request::toString(Request::POST, "dec"))}'
                WHERE `id`='{$id}';");
    Report::okay(Language::get("FAQ_UPDATED"));
    header("location: ?view=event&event=faq.main&item=".$id);
    exit;
  }
  
  private function getItemData(int $id){
    return Database::get()->query("SELECT * FROM `".DB_PREFIX."faq` WHERE `id`='{$id}'")->fetch();
  }
  
  private function create(Tempelate $tempelate, Page $page){
    if(!Request::isEmpty(Request::POST, "name") && !Request::isEmpty(Request::POST, "dec"))
      $this->createCategory(Request::toString(Request::POST, "name"), Request::toString(Request::POST, "dec"), $tempelate);
    $tempelate->render("Create", $this->tempelateDir);
  }
  
  private function createCategory(string $name, string $dec, Tempelate $tempelate){
    $tempelate->put("name", $name);
    $tempelate->put("dec",  $dec);
    $db = Database::get();
    $current = $db->query("SELECT `id` FROM `".DB_PREFIX."faq` WHERE `name`='{$db->escape($name)}'")->fetch();
    if($current){
      Report::error(Language::get("FAQ_CAT_EXISTS"));
      return;
    }
    
    $id = $db->query("INSERT INTO `".DB_PREFIX."faq` VALUES (null, '{$db->escape($name)}', '{$db->escape($dec)}');");
    Report::okay(Language::get("FAQ_CAT_CREATED"));
    header("location: ?view=event&event=faq.main&item=".$id);
    exit;
  }
}
