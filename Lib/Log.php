<?php
namespace Lib;

use Lib\Log\LogResult;

class Log{
  public static function onTicketDelete(int $id){
    Database::get()->query("DELETE FROM `log` WHERE `type`='TICKET' AND `tid`='{$id}'");
  }
  
  public static function system(string $message, ...$arg){
    self::save("SYSTEM", 0, $message, $arg);
  }
  
  public static function getSystemLog(){
    $query = Database::get()->query("SELECT * FROM `log` WHERE `type`='SYSTEM';");
    $result = [];
    while($row = $query->fetch())
      $result[] = $row;
    return new LogResult($result);
  }
  
  public static function user(int $uid, string $message, ...$arg){
    self::save("USER", $uid, $message, $arg);
  }
  
  public static function getUserLog(int $uid){
    $query = Database::get()->query("SELECT * FROM `log` WHERE `type`='USER' AND `tid`='{$uid}'");
    $result = [];
    while($row = $query->fetch()){
      $result[] = $row;
    }
    return new LogResult($result);
  }
  
  public static function ticket(int $id, string $message, ...$arg){
    self::save("TICKET", $id, $message, $arg);
  }
  
  public static function getTicketLog(int $id) : LogResult{
    $query = Database::get()->query("SELECT * FROM `log` WHERE `type`='TICKET' AND `tid`='{$id}'");
    $result = [];
    while($row = $query->fetch()){
      $result[] = $row;
    }
    return new LogResult($result);
  }
  
  private static function save(string $type, int $tid, string $msg, array $data){
     $db = Database::get();
     $db->query("INSERT INTO `log` (
       `type`,
       `created`,
       `uid`,
       `tid`,
       `message`,
       `arg`
     ) VALUES (
       '{$type}',
       NOW(),
       '".(defined("user") ? user["id"] : "0")."',
       '{$tid}',
       '{$db->escape($msg)}',
       '{$db->escape(json_encode($data))}'
     )");
  }
}