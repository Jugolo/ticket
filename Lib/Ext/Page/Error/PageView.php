<?php
namespace Lib\Ext\Page\Error;

use Lib\Controler\Page\PageView as P;
use Lib\Database;
use Lib\Database\DatabaseFetch;
use Lib\Report;
use Lib\Tempelate;
use Lib\Page;
use Lib\Access;

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
    if(!empty($_GET["id"]) && $data = $this->getData(intval($_GET["id"]))){
      $this->showError($data, $tempelate, $pageObj);
      return;
    }
    $page = !empty($_GET["ep"]) && is_numeric($_GET["ep"]) ? intval($_GET["ep"]) : 0;
    $db = Database::get();
    
    $count = $db->query("SELECT COUNT(`id`) AS id FROM `error`")->fetch()->id;
    
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
    
    $query = $db->query("SELECT `id`, `errstr` FROM `error` LIMIT {$page}, 30");
    
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
    $tempelate->render("error", $pageObj);
  }
  
  private function deleteErrors(){
    $queryString = "DELETE FROM `error` WHERE ";
    $ids = [];
    $db = Database::get();
    foreach($_POST["errorSelect"] as $id){
      $ids[] = "`id`='{$id}'";
    }
    
    Database::get()->query($queryString.implode(" OR ", $ids));
    Report::okay("Errors message is now deleted");
  }
  
  private function showError($data, $tempelate, $page){
    $tempelate->put("file",    $data->errfile);
    $tempelate->put("line",    $data->errline);
    $tempelate->put("time",    $data->errtime);
    $tempelate->put("message", $data->errstr);
    
    $file = file($data->errfile);
    $lines = [];
    $max = min(count($file), $data->errline+10)-1;
    $min = max(1, $data->errline-10)-1;
    
    for($i=$min;$i<$max;$i++){
      $lines[] = [
        "line"   => $file[$i],
        "number" => $i+1
        ];
    }
    $tempelate->put("lines", $lines);
    
    $db = Database::get();
    $query = $db->query("SELECT `id`, `errstr`
                         FROM `error`
                         WHERE `id`<>'{$data->id}'
                         AND `errstr`='{$db->escape($data->errstr)}'
                         AND `errline`='{$db->escape($data->errline)}'
                         AND `errfile`='{$db->escape($data->errfile)}'");
    $other_error = [];
    while($row = $query->fetch())
      $other_error[] = $row->toArray();
    $tempelate->put("other_error", $other_error);
    
    $tempelate->render("show_error", $page);
  }
  
  private function getData(int $id){
    return Database::get()->query("SELECT * FROM `error` WHERE `id`='{$id}'")->fetch();
  }
}