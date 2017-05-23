<?php
namespace Lib\Setup\Upgrade;

use Lib\Database;

class V23{
 public $version = "V2.3";
  
  public function upgrade(){
    $db = Database::get();
    $db->query("UPDATE `config` SET `value`='{$this->version}' WHERE `name`='version'");
    return true; 
  }
}