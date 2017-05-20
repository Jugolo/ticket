<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Database;

class Standart{
  public static function body(){
    if(empty($_GET["gid"])){
      notfound();
      return;
    }
    $db = Database::get();
    $db->query("UPDATE `group` SET `isStandart`=1 WHERE `id`='".(int)$_GET["gid"]."'");
    if($db->affected() == 0){
      html_error("Unknown group");
    }else{
      $db->query("UPDATE `group` SET `isStandart`=0 WHERE `id` <> '".(int)$_GET["gid"]."' AND `isStandart`=1");
      html_okay("The group is now the standart group");
    }
    header("location: ?view=handleGroup");
    exit;
  }
}
