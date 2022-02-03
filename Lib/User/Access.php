<?php
namespace Lib\User;

use Lib\Database;

class Access{
  protected $access = [];
  protected $user;
  
  public function __construct(User $user){
	  $this->user = $user;
	  if($user->id() == 0)
		return;
		
	  $db = Database::get()->query("SELECT acces.name FROM `".DB_PREFIX."access` AS acces LEFT JOIN `".DB_PREFIX."grup_member` AS member ON acces.gid=member.gid WHERE member.uid='".$user->id()."' GROUP BY acces.name;");
	  while($row = $db->fetch()){
		 $this->access[] = $row->name; 
	  }
  }
  
  public function has(string $key) : bool{
	  return in_array($key, $this->access);
  }
  
  public function hasMuliAccess(array $keys) : bool{
	  for($i=0;$i<count($keys);$i++){
		  if($this->has($keys[$i]))
			return true;
	  }
	  
	  return false;
  }
}
