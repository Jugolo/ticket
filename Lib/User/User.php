<?php
namespace Lib\User;

use Lib\Language\Language;
use Lib\Language\LanguageDetector;
use Lib\Request;
use Lib\Database;

class User{
	private $data;
	private $access;
	
	public function __construct(?array $data){
		if($data == null){
			$data = [
			  "id" => 0
		    ];
		}
		
		$this->data = $data;			
		$this->getLanguage();
	}
	
	public function id() : int{
		return (int)$this->data["id"];
	}
	
	public function email() : string{
		if($this->data["id"] == 0)
			return "";
		return $this->data["email"];
	}
	
	public function password() : string{
		if($this->data["id"] == 0)
			return "";
		return $this->data["password"];
	}
	
	public function salt() : string{
		if($this->data["id"] == 0)
			return "";
		return $this->data["salt"];
	}
	
	public function username() : string{
		if($this->data["id"] == 0){
			return "Geaust";
		}
		return $this->data["username"];
	}
	
	public function birth() : ?UserBirth{
		if(!array_key_exists("birth_day", $this->data) || $this->data["birth_day"] == null)
			return null;
		return new UserBirth(
			$this->data["birth_day"],
			$this->data["birth_month"],
			$this->data["birth_year"]
		);
	}
	
	public function access() : Access{
		if(!$this->access)
			$this->access = new Access($this);
		return $this->access;
	}
	
	public function isLoggedIn() : bool{
		return $this->data["id"] != 0;
	}
	
	private function getLanguage(){
		$langget = null;
		
		if(!Request::isEmpty(Request::GET, "lang")){
			$path = "Lib/Ext/Language/".Request::toString(Request::GET, "lang")."/";
			if(file_exists($path) && is_dir($path)){
				$langget = Request::toString(Request::GET, "lang");
			}
		}
		
		if($this->isLoggedIn()){
		  if($langget){
			  $db = Database::get();
			  $db->query("UPDATE `".DB_PREFIX."user` SET `lang`='{$db->escape($langget)}' WHERE `id`='{$this->id()}'");
		  }
          Language::newState("Lib/Ext/Language/".$this->data["lang"]."/");
        }else{
		  if(!empty($_COOKIE["accept_cookie"])){
			  setcookie("lang", $langget, time() + (86400 * 30), "/");
		  }
          LanguageDetector::detect();
	    }
	    
	    if($langget){
			header("location: index.php");
			exit;
		}
	}
}
