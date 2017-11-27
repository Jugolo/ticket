<?php
namespace Lib\Tempelate;

class PageAccessNode implements TempelateNode{
  private $identify;
  
  public function __construct(string $identify){
    $this->identify = $identify;
  }
  
  public function toCode() : string{
    return "\$this->page->hasAccessTo('{$this->identify}')";
  }
}