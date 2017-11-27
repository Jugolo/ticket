<?php
namespace Lib\Plugin;

use Lib\Database;

class Plugin{
  private static $events = null;
  
  public static function init(){
    if(self::isInit()){
      return;
    }
    
    self::$events = [
      "system.category.delete" => [
        "Lib\\Ticket\\TicketDeleter::onCategoryDelete"
      ],
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
    
    self::loadPluginEvents();
  }
  
  public static function isInit() : bool{
    return self::$events !== null;
  }
  
  public static function install(string $path){
    if(!self::isInit())
      self::init();
    
    if(!file_exists($path."info.xml"))
      return false;
    
    $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
    if(!$xml->events || !$xml->events->event)
      return false;
    
    foreach($xml->events->event as $node){
      if(!$node["src"])
        continue;
      
      $pp = $path.str_replace(".", "/", (string)$node["src"]);
      $class = str_replace("/", "\\", $pp);
      if(!file_exists($pp.".php"))
        return false;
      
      include $pp.".php";
      
      if(!class_exists($class))
        return false;
      
      $obj = new $class();
      if(!($obj instanceof PluginInterface))
        return false;
    }
    
    if($xml->setup && $xml->setup->install){
      call_user_func(str_replace("/", "\\", $path).((string)$xml->setup->install["call"]));
    }
    
    $db = Database::get();
    $db->query("INSERT INTO `plugin` VALUES (NULL, '{$db->escape($path)}');");
    return true;
  }
  
  public static function uninstall(string $path){
    if(file_exists($path."info.xml")){
      $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
      if($xml->setup && $xml->setup->uninstall && $xml->setup->uninstall["call"])
        call_user_func(str_replace("/", "\\", $path).((string)$xml->setup->uninstall["call"]));
    }
    $db = Database::get();
    $db->query("DELETE FROM `plugin` WHERE `path`='{$path}';");
  }
  
  public static function trigger_event(string $event, ...$arg){
    if(!empty(self::$events[$event])){
      foreach(self::$events[$event] as $e){
        call_user_func_array($e, $arg);
      }
    }
  }
  
  private static function loadPluginEvents(){
    $events = self::$events;
    Database::get()->query("SELECT `path` FROM `plugin`")->fetch(function($path) use(&$events){
      if(file_exists($path."info.xml")){
        $xml = new \SimpleXMLElement(file_get_contents($path."info.xml"));
        if($xml->events){
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
    });
    self::$events = $events;
  }
}