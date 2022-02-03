<?php
namespace Lib\Plugin;

class Event{
  private $stopped = false;
  
  public function stop(){
    $this->stopped = true;
  }
  
  public function isStopped(){
    return $this->stopped;
  }
}