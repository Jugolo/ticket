<?php
namespace Lib;

use Lib\User\Access;
use Lib\User\User;

class CatAccess extends Access{
	public function __construct(int $cid, User $user){
	  $this->user = $user;
	  if($user->id() == 0)
		return;
		
	  $db = Database::get()->query("SELECT acces.name FROM `".DB_PREFIX."category_access` AS acces LEFT JOIN `".DB_PREFIX."grup_member` AS member ON acces.gid=member.gid WHERE member.uid='".$user->id()."' AND acces.cid='{$cid}' GROUP BY acces.name;");
	  while($row = $db->fetch()){
		 $this->access[] = $row->name; 
	  }
	}
}
