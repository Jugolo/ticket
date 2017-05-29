<?php
namespace Lib\Setup\Upgrade;
use Lib\Database;
class V25{
 public $version = "V2.5";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    return true; 
  }
}
