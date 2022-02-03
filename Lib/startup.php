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
use Lib\Language\LanguageLister;
use Lib\Page;
use Lib\Cronwork;

header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

define("IP", $_SERVER['REMOTE_ADDR']);

if(!empty($_COOKIE["accept_cookie"]))
  session_start();

define("BASE", dirname(__FILE__, 2)."/");
set_include_path(BASE);

include 'Lib/Loader.php';

Loader::set();
Error::collect();

function get_extension(string $file) : string{
  $pos = strrpos($file, ".");
  if($pos === false)
    return $file;
  return substr($file, $pos+1);
}

function rand_string(int $length) : string{
  $return = "";
  for($i=0;$i<$length;$i++)
    $return .= chr(mt_rand(0, 255));
  return $return;
}

register_shutdown_function(function(){
  $error = error_get_last();
  if($error){
    $db = Database::get();
    $db->query("INSERT INTO `".DB_PREFIX."error` (
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

if(!defined("DB_PREFIX"))
  define("DB_PREFIX", "");

$user = Auth::controleAuth();

if(file_exists("./Lib/Setup/Main.php")){
    Main::controle();
}

Cronwork::check();

try{
  $page = new Page($user);
  $tempelate = new Tempelate(Config::get("tempelate"), $page, $user);
}catch(TempelateException $e){
  Error::tempelateError($e);
  exit;
}
Language::load("default");
Plugin::init($tempelate);

if(empty($_COOKIE["accept_cookie"])){
  if(!empty($_GET["accept_cookie"])){
    setcookie("accept_cookie", "true", time() + (86400 * 30), "/");
    header("location: ./");
    exit;
  }
  $tempelate->put("no_cookie", true);
  Language::load("cookie");
}

if(Ajax::isAjaxRequest()){
  Ajax::evulate();
  exit;
}

Plugin::trigger_event("system.started");

$lang_list = LanguageLister::list();
if(count($lang_list) > 1){
	$tempelate->put("show_lang_menu", true);
	$tempelate->put("current_lang_code", Language::getCode());
	$tempelate->put("current_lang_flag", Language::getFlagBase64());
	$tempelate->put("lang_list", $lang_list);
}
