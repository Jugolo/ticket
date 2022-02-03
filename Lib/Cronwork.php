<?php
namespace Lib;

class Cronwork{
  public static function check(){
    $query = Database::get()->query("SELECT `id`, `cronwork`, `interval` FROM `".DB_PREFIX."cronwork` WHERE `next`<'".time()."'");
    while($row = $query->fetch())
      self::doCron($row->id, $row->cronwork, $row->interval);
  }
  
  private static function doCron(int $id, string $cronwork, int $interval){
    call_user_func($cronwork);
    Database::get()->query("UPDATE `".DB_PREFIX."cronwork` SET `next`='".(time() + $interval)."' WHERE `id`='{$id}'");
  }
}