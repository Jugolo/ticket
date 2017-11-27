<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Template;

use Lib\Database;
use Lib\Report;
use Lib\Tempelate;
use Lib\Access as A;
use Lib\Page;

class Access{
  public static function body(Tempelate $tempelate, Page $page){
    if(empty($_GET["gid"]) || !A::userHasAccess("GROUP_ACCESS")){
      return;
    }
    
    $data = self::getData();
    if(!$data){
      Report::error("Unknown group");
      return false;
    }
    
    $group = [];
    foreach(A::getRawAccess(intval($_GET["gid"])) as $value)
      $group[$value] = true;
    
    if($group === null){
      Report::error("Unknown group");
      return false;
    }
  
    if(!empty($_POST["update"])){
      self::update_access(intval($_GET["gid"]), $group);
    }
    
    $tempelate->put("group", $group);
    $tempelate->put("name", $data->name);
    $tempelate->render("group_access", $page);
    return true;
  }
  
  private static function update_access(int $gid, array $group){
    //wee has this array to esey to render optiones and append or delete access points
    $accesses = [
      "CATEGORY_CREATE",
      "CATEGORY_DELETE",
      "CATEGORY_CLOSE",
      "CATEGORY_APPEND",
      "CATEGORY_ITEM_DELETE",
      "CATEGORY_SETTING",
      "TICKET_OTHER",
      "TICKET_CLOSE",
      "TICKET_DELETE",
      "COMMENT_DELETE",
      "TICKET_LOG",
      "USER_GROUP",
      "USER_PROFILE",
      "USER_DELETE",
      "USER_LOG",
      "USER_ACTIVATE",
      "GROUP_CREATE",
      "GROUP_DELETE",
      "GROUP_ACCESS",
      "GROUP_STANDART",
      "ERROR_SHOW",
      "ERROR_DELETE",
      "SYSTEM_FRONT",
      "SYSTEM_NAME",
      "SYSTEMLOG_SHOW",
      "TEMPELATE_SELECT",
      "TICKET_SEEN",
      "PLUGIN_INSTALL",
      "PLUGIN_UNINSTALL"
      ];
    $append = [];
    $delete = [];
    foreach($accesses as $access){
      if(!empty($group[$access]) && empty($_POST[$access]))
        $delete[] = $access;
      elseif(empty($group[$access]) && !empty($_POST[$access]))
        $append[] = $access;
    }
    
    if(count($append) == 0 && count($delete) == 0){
      Report::error("No update detected");
    }else{
      if(count($append) > 0)
        A::appendAccesses($gid, $append);
      if(count($delete) > 0)
        A::deleteAccesses($gid, $delete);
      Report::okay("Access updated");
    }
    header("location: ?view=handleGroup&sub=Access&gid=".$gid);
    exit;
  }
  
  private static function getData(){
    $db = Database::get();
    return $db->query("SELECT * FROM `group` WHERE `id`='{$db->escape($_GET["gid"])}';")->fetch();
  }
}