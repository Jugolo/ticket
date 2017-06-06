<?php
namespace Lib\Setup\Upgrade;
use Lib\Database;
class V26{
 public $version = "V2.6";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    return true; 
  }
}
