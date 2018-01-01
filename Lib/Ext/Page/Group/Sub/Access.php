<?php
namespace Lib\Ext\Page\Group\Sub;

use Lib\Template;

use Lib\Database;
use Lib\Report;
use Lib\Tempelate;
use Lib\Access as A;
use Lib\Page;
use Lib\Language\Language;
use Lib\Access\AccessTreeBuilder;
use Lib\Plugin\Plugin;

class Access{
  public static function body(Tempelate $tempelate, Page $page){
    if(empty($_GET["gid"]) || !A::userHasAccess("GROUP_ACCESS")){
      return;
    }
    
    $tree = self::buildAccessTree($tempelate);
    
    $data = self::getData();
    if(!$data){
      Report::error(Language::get("UNKNOWN_GROUP"));
      return false;
    }
    
    $group = [];
    foreach(A::getRawAccess(intval($_GET["gid"])) as $value)
      $group[$value] = true;
    
    if($group === null){
      Report::error(Language::get("UNKNOWN_GROUP"));
      return false;
    }
  
    if(!empty($_POST["update"])){
      self::update_access(intval($_GET["gid"]), $group, $tree);
    }
    
    $tempelate->put("group", $group);
    $tempelate->put("name", $data->name);
    $tempelate->render("group_access");
    return true;
  }
  
  private static function update_access(int $gid, array $group, AccessTreeBuilder $builder){
    $append = [];
    $delete = [];
    $accesses = $builder->accessKeys();
    
    foreach($accesses as $access){
      if(!empty($group[$access]) && empty($_POST[$access]))
        $delete[] = $access;
      elseif(empty($group[$access]) && !empty($_POST[$access]))
        $append[] = $access;
    }
    
    if(count($append) == 0 && count($delete) == 0){
      Report::error(Language::get("NO_UPDATE"));
    }else{
      if(count($append) > 0)
        A::appendAccesses($gid, $append);
      if(count($delete) > 0)
        A::deleteAccesses($gid, $delete);
      Report::okay(Language::get("ACCESS_UPDATED"));
    }
    header("location: ?view=handleGroup&sub=Access&gid=".$gid);
    exit;
  }
  
  private static function getData(){
    $db = Database::get();
    return $db->query("SELECT * FROM `group` WHERE `id`='{$db->escape($_GET["gid"])}';")->fetch();
  }
  
  private static function buildAccessTree(Tempelate $tempelate) : AccessTreeBuilder{
    $tree = new AccessTreeBuilder();
    
    $cat    = Language::get("CATEGORY");
    $ticket = Language::get("TICKET");
    $user   = Language::get("USER");
    $group  = Language::get("GROUP");
    $error  = Language::get("ERROR");
    $system = Language::get("SYSTEM");
    
    $tree->createCategory($cat);
    $tree->setItem($cat, "CATEGORY_CREATE",      "CREATE_CATEGORY");
    $tree->setItem($cat, "CATEGORY_DELETE",      "DELETE_CATEGORY");
    $tree->setItem($cat, "CATEGORY_CLOSE",       "O_C_CATEGORY");
    $tree->setItem($cat, "CATEGORY_SORT",        "CATEGORY_SORT");
    $tree->setItem($cat, "CATEGORY_APPEND",      "APPEND_INPUT");
    $tree->setItem($cat, "CATEGORY_ITEM_DELETE", "DELETE_INPUT");
    $tree->setItem($cat, "CATEGORY_SETTING",     "CHANGE_SETTING");
    
    $tree->createCategory($ticket);
    $tree->setItem($ticket, "TICKET_OTHER",   "SHOW_TICKET");
    $tree->setItem($ticket, "TICKET_CLOSE",   "C_O_TICKET");
    $tree->setItem($ticket, "TICKET_DELETE",  "DELETE_TICKET");
    $tree->setItem($ticket, "COMMENT_DELETE", "DELETE_COMMENTS");
    $tree->setItem($ticket, "TICKET_LOG",     "S_TICKET_LOG");
    $tree->setItem($ticket, "TICKET_SEEN",    "SEEN_TICKET");
    
    $tree->createCategory($user);
    $tree->setItem($user, "USER_GROUP",    "CHANGE_U_GROUP");
    $tree->setItem($user, "USER_PROFILE",  "SEE_PROFILE");
    $tree->setItem($user, "USER_DELETE",   "DELETE_USER");
    $tree->setItem($user, "USER_LOG",      "USER_LOG");
    $tree->setItem($user, "USER_ACTIVATE", "ACTIVATE_USER");
    
    $tree->createCategory($group);
    $tree->setItem($group, "GROUP_CREATE",   "CREATE_GROUP");
    $tree->setItem($group, "GROUP_DELETE",   "DELETE_GROUP");
    $tree->setItem($group, "GROUP_ACCESS",   "CHANGE_ACCESS");
    $tree->setItem($group, "GROUP_STANDART", "CHANGE_STANDART");
    
    $tree->createCategory($error);
    $tree->setItem($error, "ERROR_SHOW",   "SHOW_ERROR");
    $tree->setItem($error, "ERROR_DELETE", "DELETE_ERROR");
    
    $tree->createCategory($system);
    $tree->setItem($system, "SYSTEM_FRONT",     "CHANGE_FRONT");
    $tree->setItem($system, "SYSTEM_NAME",      "CHANGE_NAME");
    $tree->setItem($system, "SYSTEMLOG_SHOW",   "G_SYSTEM_LOG");
    $tree->setItem($system, "TEMPELATE_SELECT", "CHANGE_TEMPELATE");
    $tree->setItem($system, "PLUGIN_INSTALL",   "INSTALL_PLUGIN");
    $tree->setItem($system, "PLUGIN_UNINSTALL", "UNINSTALL_PLUGIN");
    
    Plugin::trigger_event("system.access.get", $tree);
    
    $tree->setTempelate($tempelate);
    return $tree;
  }
}