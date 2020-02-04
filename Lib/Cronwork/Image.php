<?php
namespace Lib\Cronwork;

use Lib\Database;

class Image{
  public static function gc(){
    $db = Database::get();
    $db->query("SELECT files.id, files.name
                FROM `".DB_PREFIX."files` AS files
                LEFT JOIN `".DB_PREFIX."ticket_value` AS ticket_value ON ticket_value.value=files.id AND ticket_value.type='4'
                WHERE ticket_value.id IS NULL")->fetch(function(int $id, string $name) use($db){
      unlink("Lib/Uploaded/".$name);
      $db->query("DELETE FROM `".DB_PREFIX."files` WHERE `id`='{$id}'");
    });
  }
}