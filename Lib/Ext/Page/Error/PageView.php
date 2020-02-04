<?php
namespace Lib\Ext\Page\Error;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Report;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;
use Lib\Language\Language;

class PageView implements P{
  public function loginNeeded() : string{
    return "YES";
  }
  
  public function identify() : string{
    return "error";
  }
  
  public function access() : array{
    return ["ERROR_SHOW"];
  }
  
  function body(Tempelate $tempelate, Page $pageObj){
    Language::load("error");
    if(!empty($_GET["id"]) && $data = $this->getData(intval($_GET["id"]))){
      $this->showError($data, $tempelate);
      return;
    }
    $page = !empty($_GET["ep"]) && is_numeric($_GET["ep"]) ? intval($_GET["ep"]) : 0;
    $db = Database::get();
    
    $count = $db->query("SELECT COUNT(`id`) AS id FROM `".DB_PREFIX."error`")->fetch()->id;
    
    if($page*30 > $count){
      $page = round($count/30);
    }
    
    $link = [];
    for($i=max($page-10, 0);$i<min($page+10, $count/30);$i++){
      $link[] = [
        "isCurrent" => $i == $page,
        "link"      => "?view=error&ep=".$i,
        "name"      => $i+1
        ];
    }
    $tempelate->put("links", $link);
    
    $query = $db->query("SELECT `id`, `errstr` FROM `".DB_PREFIX."error` LIMIT {$page}, 30");
    
    if(!empty($_POST["delete"]) && !empty($_POST["errorSelect"]) && Access::userHasAccess("ERROR_DELETE")){
      $this->deleteErrors();
      header("location: #");
      exit;
    }
    $errors = [];
    while($row = $query->fetch()){
      $errors[] = $row->toArray();
    }
    $tempelate->put("system_error", $errors);
    $tempelate->render("error");
  }
  
  private function deleteErrors(){
    $queryString = "DELETE FROM `".DB_PREFIX."error` WHERE ";
    $ids = [];
    $db = Database::get();
    foreach($_POST["errorSelect"] as $id){
      $ids[] = "`id`='{$id}'";
    }
    
    Database::get()->query($queryString.implode(" OR ", $ids));
    Report::okay(Language::get("ERROR_DELETED"));
  }
  
  private function showError($data, $tempelate){
    $tempelate->put("file",    $data->errfile);
    $tempelate->put("line",    $data->errline);
    $tempelate->put("time",    $data->errtime);
    $tempelate->put("message", $data->errstr);
    
    $lines = [];
    if(file_exists($data->errfile)){
      $file = file($data->errfile);
      $max = min(count($file), $data->errline+10);
      $min = max(1, $data->errline-10)-1;
    
      for($i=$min;$i<$max;$i++){
        $lines[] = [
          "line"   => $file[$i],
          "number" => $i+1
        ];
      }
    }
    $tempelate->put("lines", $lines);
    
    $db = Database::get();
    $query = $db->query("SELECT `id`, `errstr`
                         FROM `".DB_PREFIX."error`
                         WHERE `id`<>'{$data->id}'
                         AND `errstr`='{$db->escape($data->errstr)}'
                         AND `errline`='{$db->escape($data->errline)}'
                         AND `errfile`='{$db->escape($data->errfile)}'
                         LIMIT 0, 30");
    $other_error = [];
    while($row = $query->fetch())
      $other_error[] = $row->toArray();
    $tempelate->put("other_error", $other_error);
    
    $tempelate->render("show_error");
  }
  
  private function getData(int $id){
    return Database::get()->query("SELECT * FROM `".DB_PREFIX."error` WHERE `id`='{$id}'")->fetch();
  }
}