<?php
use Lib\Controler\Page\PageControler;
use Lib\Controler\Page\PageInfo;
use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Report;
use Lib\Config;
use Lib\Plugin\Plugin;
use Lib\Ajax;
use Lib\User\Auth;
use Lib\Tempelate;
use Lib\Exception\TempelateException;
use Lib\Error;
use Lib\Page;

include 'Lib/startup.php';

function updateUserGroup(Lib\Database\DatabaseFetch $user, $id){
  Database::get()->query("UPDATE `user` SET `groupid`='".(int)$id."' WHERE `id`='".(int)$user->id."'");
}

function getUsergroup(int $id){
  static $buffer = [];
  if(!array_key_exists($id, $buffer)){
   $buffer[$id] = Database::get()->query("SELECT * FROM `group` WHERE `id`='".(int)$id."'")->fetch()->toArray();  
  }
  return $buffer[$id];
}

function notfound(){
  header("HTTP/1.0 404 Not Found");
  echo "The request page was not found....";
}

function geturl(){
  return "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
}

function getStandartGroup(){
  return Lib\Config::get("standart_group");
}

try{
  $tempelate->put("session_id", session_id());
  if(defined("user")){
    $tempelate->put("username", user["username"]);
  }
  $page = new Page();
  $page->show(empty($_GET["view"]) ? "front" : $_GET["view"], $tempelate);
}catch(TempelateException $e){
  Error::tempelateError($e);
}