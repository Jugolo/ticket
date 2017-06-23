<?php
namespace Lib\Setup\Upgrade;
use Lib\Database;
class V27{
 public $version = "V2.7";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    return true; 
  }
}
