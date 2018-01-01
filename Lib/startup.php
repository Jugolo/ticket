<?php
//this file is to allow multiply file instance only index.php
use Lib\Loader;
use Lib\Error;
use Lib\Setup\Main;
use Lib\Ajax;
use Lib\Plugin\Plugin;
use Lib\User\Auth;
use Lib\Database;
use Lib\Tempelate;
use Lib\Config;
use Lib\Exception\TempelateException;
use Lib\Language\Language;
use Lib\Page;

header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
session_start();

define("BASE", dirname(__FILE__, 2)."/");
set_include_path(BASE);

include 'Lib/Loader.php';

Loader::set();
Error::collect();

register_shutdown_function(function(){
  $error = error_get_last();
  if($error){
    $db = Database::get();
    $db->query("INSERT INTO `error` (
      `errno`,
      `errstr`,
      `errfile`,
      `errline`,
      `errtime`
    ) VALUES (
      '".$db->escape($error["type"])."',
      '".$db->escape($error["message"])."',
      '".$db->escape($error["file"])."',
      '".$db->escape($error["line"])."',
      NOW()
    )");
  }
  if(Database::isInit()){
    Database::get()->close();
  }
});

if(!file_exists("config.php")){
  Main::controle();
}

include 'config.php';

Auth::controleAuth();

if(defined("user"))
  Language::newState("Lib/Ext/Language/".user["lang"]."/");
else
  Lib\Language\LanguageDetector::detect();

if(file_exists("./Lib/Setup/Main.php")){
    Main::controle();
}

try{
  $page = new Page();
  $tempelate = new Tempelate(Config::get("tempelate"), $page);
}catch(TempelateException $e){
  Error::tempelateError($e);
  exit;
}
Language::load("default");
Plugin::init($tempelate);

if(Ajax::isAjaxRequest()){
  Ajax::evulate();
  exit;
}

Plugin::trigger_event("system.started");