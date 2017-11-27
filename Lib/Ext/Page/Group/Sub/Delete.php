<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Access;
use Lib\Report;
use Lib\Config;
use Lib\Group;

class Delete{
  public static function body(){
    if(empty($_GET["gid"]) || !Access::userHasAccess("GROUP_DELETE"))
      return;
    
    if(Config::get("standart_group") == $_GET["gid"]){
      Report::error("The group can`t be deleted becuse it is standart group!");
      header("location: ?view=handleGroup");
      exit;
    }
    Group::delete(intval($_GET["gid"]));
    Report::okay("The group is delteded");
    header("location: ?view=handleGroup");
    exit;
  }
}