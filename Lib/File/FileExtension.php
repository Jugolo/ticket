<?php
namespace Lib\File;

use Lib\Cache;
use Lib\Database;
use Lib\Language\Language;

if(!Language::load("file"))
  exit("Failed to get file language");

class FileExtension{
  public function download(int $ticket_id, int $file_id, string $name) : bool{
    $data = Database::get()->query("SELECT `name` FROM `".DB_PREFIX."files` WHERE `item_id`='{$ticket_id}' AND `id`='{$file_id}'")->fetch();
    if(!$data){
      return false;
    }
    
    $file = "Lib/Uploaded/".$data->name;
    if(!file_exists($file)){
      exit($file);
      return false;
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: '.$this->getMimetype(get_extension($data->name)));
    header('Content-Disposition: attachment; filename="'.$name.'.'.get_extension($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
  }
  
  public function getMimetype(string $extension){
    $db = Database::get();
    $data = $db->query("SELECT `mimetype` FROM `".DB_PREFIX."file_extension` WHERE `name`='{$db->escape($extension)}'")->fetch();
    return $data ? $data->mimetype : "";
  }
  
  public function createFile(int $ticket_id, string $extentsion) : array{
    $name = sha1(rand_string(250)).".".$extentsion;
    $db = Database::get();
    $id = $db->query("INSERT INTO `".DB_PREFIX."files` (
      `id`,
      `item_id`,
      `name`,
      `created`
    ) VALUES (
      NULL,
      '{$db->escape($ticket_id)}',
      '{$db->escape($name)}',
      '".time()."'
    )");
    
    return [$id, $name];
  }
  
  public function getGroup() : array{
    $query = Database::get()->query("SELECT * FROM `".DB_PREFIX."file_group`");
    $return = [];
    while($row = $query->fetch())
      $return[] = new FileExtensionGroup($row->id, $row->name);
    return $return;
  }
  
  public function isSupported(int $group, string $filename) : bool{
    $pos = strrpos($filename, ".");
    if($pos === false)
      $n = $filename;
    else
      $n = substr($filename, $pos + 1);
    
    $db = Database::get();
    $query = $db->query("SELECT `id` FROM `".DB_PREFIX."file_extension` WHERE `name`='{$db->escape($n)}' AND `gid`='{$group}'");
    return $query->fetch() ? true : false;
  }
  
  public function getGroupName(int $id) : string{
    $db = Database::get();
    $query = $db->query("SELECT `name` FROM `".DB_PREFIX."file_group` WHERE `id`='{$db->escape($id)}'");
    $data = $query->fetch();
    if(!$data)
      return "";
    $name = $data->name;
    if(strpos($name, "@language.") === 0)
      return Language::get(substr($name, 10));
    return $name;
  }
}