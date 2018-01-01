<?php
namespace Lib\Plugin;

use Lib\Database;
use Lib\Language\Language;
use Lib\Tempelate;
use Lib\Exception\PluginInstallException;
use Lib\Exception\TempelateFileNotFound;
use Lib\Page;
use Lib\Uri;
use Lib\Controler\Page\PageView;

class Plugin{
  private static $events = null;
  
  public static function init(Tempelate $tempelate){
    if(self::isInit()){
      return;
    }
    
    self::$events = [
      "system.ticket.delete" => [
        "Lib\\Ticket\\TicketDeleter::onTicketDelete",
        "Lib\\Ext\\Notification\\NewTicket::onTicketDelete",
        "Lib\\Ext\\Notification\\NewComment::onTicketDelete",
        "Lib\\Log::onTicketDelete"
      ],
      "system.comment.delete" => [
        "Lib\\Ticket\\TicketDeleter::onCommentDelete"
      ],
      "ajax.update" => [
        "Lib\\Ticket\\Track::ajaxUpdate",
        "Lib\\Ext\\Notification\\Notification::ajax"
      ]
    ];
    
    self::loadPluginEvents($tempelate);
  }
  
  public static function isInit() : bool{
    return self::$events !== null;
  }
  
  public static function install(string $path){
    if(!self::isInit())
      return;
    
    if(!file_exists($path."info.xml"))
      throw new PluginInstallException("MISSING_INFO");
    
    $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
    if(!$xml->events || !$xml->events->event)
      throw new PluginInstallException("NO_EVENT");
    
    foreach($xml->events->event as $node){
      if(!$node["src"])
        continue;
      
      $pp = $path.str_replace(".", "/", (string)$node["src"]);
      $class = str_replace("/", "\\", $pp);
      if(!file_exists($pp.".php"))
        throw new PluginInstallException("MISSING_CLASS");
      
      include $pp.".php";
      
      if(!class_exists($class))
        throw new PluginInstallException("MISSING_CLASS");
      
      $obj = new $class();
      if(!($obj instanceof PluginInterface))
        throw new PluginInstallException("INVALID_CLASS");
    }
    
    if($xml->setup && $xml->setup->install){
      call_user_func(str_replace("/", "\\", $path).((string)$xml->setup->install["call"]));
    }
    
    $db = Database::get();
    $db->query("INSERT INTO `plugin` VALUES (NULL, '{$db->escape($path)}');");
    PluginRender::unset();
  }
  
  public static function uninstall(string $path){
    if(file_exists($path."info.xml")){
      $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
      if($xml->setup && $xml->setup->uninstall && $xml->setup->uninstall["call"])
        call_user_func(str_replace("/", "\\", $path).((string)$xml->setup->uninstall["call"]));
    }
    $db = Database::get();
    $db->query("DELETE FROM `plugin` WHERE `path`='{$path}';");
    PluginRender::unset();
  }
  
  public static function trigger_event(string $events, ...$arg) : bool{
    if(!empty(self::$events[$events])){
      $event = new Event();
      $arg = array_merge([$event], $arg);
      foreach(self::$events[$events] as $e){
        call_user_func_array($e, $arg);
        if($event->isStopped())
          return false;
      }
    }
    return true;
  }
  
  public static function trigger_tempelate(Tempelate $tempelate, string $name) : string{
    return $tempelate->render_plugin($name);
  }
  
  public static function trigger_page(string $identify, Tempelate $tempelate, Page $page){
    //exit($identify);
    $result = PluginRender::render(function($path) use($identify, $tempelate, $page){
      if(!file_exists($path."info.xml"))
        return true;
      
      $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
      if(!$xml->pages || !$xml->pages->page)
        return true;
      
      foreach($xml->pages->page as $pages){
        if((string)$pages["event"] == $identify){
          if($pages["handler"]){
            self::doPageHandler($path, (string)$pages["handler"], $identify, $tempelate, $page);
          }
        }
      }
      return true;
    });
    $page->notfound($tempelate);
  }
  
  private static function doPageHandler(string $path, string $name, string $identify, Tempelate $tempelate, Page $page){
    $file = $path.str_replace(".", "/", $name).".php";
    if(!file_exists($file))
      return;//page handler file is not exists!!
    
    include $file;
    $class = str_replace("/", "\\", $path).str_replace(".", "\\", $name);
    
    if(!class_exists($class))
      return;
    
    $obj = new $class();
    
    if(!($obj instanceof PageView) || $obj->identify() != $identify)
      return;
    
    $l = $obj->loginNeeded();
    if($l == "YES" && !defined("user") || $l == "NO" && defined("user"))
      return;
    
    if(!$page->hasAccess($obj->access()))
      return;
    
    $obj->body($tempelate, $page);
  }
  
  private static function loadPluginEvents(Tempelate $tempelate){
    $events = self::$events;
    PluginRender::render(function($path) use(&$events, $tempelate){
      Language::renderPluginDir($path);
      $tempelate->newStack($path."Tempelate/{$tempelate->getMainName()}/");
      if(file_exists($path."info.xml")){
        $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
        if($xml->events->event){
          foreach($xml->events->event as $node){
            $class = str_replace("/", "\\", $path).str_replace(".", "\\", (string)$node["src"]);
            $obj = new $class();
            foreach($obj->getEvents() as $name => $event){
              if(empty($events[$name]))
                $events[$name] = [];
              $events[$name][] = $event;
            }
          }
        }
      }
      return true;
    });
    self::$events = $events;
  }
}