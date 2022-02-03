<?php
namespace Lib\File;

use Lib\Language\Language;

class FileExtensionGroup{
  private $id;
  private $name;
  
  public function __construct(int $id, string $name){
    $this->id   = $id;
    $this->name = $name;
  }
  
  public function getID() : int{
    return $this->id;
  }
  
  public function getName() : string{
    if(strpos($this->name, "@language.") === 0)
      return Language::get(trim(substr($this->name, 10)));
    return $this->name;
  }
}