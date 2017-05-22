<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Database;
use Lib\Error;
use Lib\Okay;

class Standart{
  public static function body(){
    if(empty($_GET["gid"])){
      notfound();
      return;
    }
    $db = Database::get();
    $db->query("UPDATE `group` SET `isStandart`=1 WHERE `id`='".(int)$_GET["gid"]."'");
    if($db->affected() == 0){
      Error::report("Unknown group");
    }else{
      $db->query("UPDATE `group` SET `isStandart`=0 WHERE `id` <> '".(int)$_GET["gid"]."' AND `isStandart`=1");
      Okay::report("The group is now the standart group");
    }
    header("location: ?view=handleGroup");
    exit;
  }
}