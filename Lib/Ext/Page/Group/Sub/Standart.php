<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Database;
use Lib\Report;
use Lib\Config;

class Standart{
  public static function body(){
    if(empty($_GET["gid"])){
      notfound();
      return;
    }
    Config::set("standart_group", $_GET["gid"]);
    Report::okay("The group is now the standart group");
    header("location: ?view=handleGroup");
    exit;
  }
}