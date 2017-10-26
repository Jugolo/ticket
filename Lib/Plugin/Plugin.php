<?php
namespace Lib\Plugin;

use Lib\Ajax;

class Plugin{
  private static $plugins = null;
  
  public static function init(){
    if(self::isInit()){
      return;
    }
    
    self::$plugins = [];
    self::putEvents("system.category.delete", "Lib\\Ticket\\TicketDeleter::onCategoryDelete");
    self::putEvents("system.ticket.delete",   "Lib\\Ticket\\TicketDeleter::onTicketDelete");
    self::putEvents("system.ticket.delete",   "Lib\\Ext\\Notification\\NewTicket::onTicketDelete");
    self::putEvents("system.ticket.delete",   "Lib\\Ext\\Notification\\NewComment::onTicketDelete");
    self::PutEvents("system.ticket.delete",   "Lib\\Log::onTicketDelete");
    self::PutEvents("system.comment.delete",  "Lib\\Ticket\\TicketDeleter::onCommentDelete");
    self::PutEvents("ajax.update",            "Lib\\Ticket\\Track::ajaxUpdate");
    self::PutEvents("ajax.update",            "Lib\\Ext\\Notification\\Notification::ajax");
    
    self::PutEvents("ajax.update", function(){
      Ajax::set("is_user", defined("user"));
    });
  }
  
  public static function isInit() : bool{
    return self::$plugins !== null;
  }
  
  public static function trigger_event(string $event, ...$arg){
    if(!empty(self::$plugins[$event])){
      foreach(self::$plugins[$event] as $e){
        call_user_func_array($e, $arg);
      }
    }
  }
  
  private static function putEvents(string $name, $data){
    if(!self::isInit()){
      self::init();
    }
    
    if(empty(self::$plugins[$name])){
      self::$plugins[$name] = [];
    }
    
    self::$plugins[$name][] = $data;
  }
}