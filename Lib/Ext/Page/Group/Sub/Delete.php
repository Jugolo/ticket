<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Database;
use Lib\Error;
use Lib\Okay;
use Lib\Config;

class Delete{
  public static function body(){
    if(empty($_GET["gid"])){
      notfound();
      return;
    }
    
    if(Config::get("standart_group") == $_GET["gid"]){
      Error::report("The group can`t be deleted becuse it is standart group!");
      header("location: ?view=handleGroup");
      exit;
    }
    $db = Database::get();
    $db->query("UPDATE `user` SET `groupid`='".Config::get("standart_group")."' WHERE `groupid`='".$db->escape($_GET["gid"])."'");
    $db->query("DELETE FROM `group` WHERE `id`='".(int)$_GET["gid"]."'");
    Okay::report("The group is delteded");
    header("location: ?view=handleGroup");
    exit;
  }
}