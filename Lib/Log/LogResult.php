<?php
namespace Lib\Log;

class LogResult{
  private $data;
  
  public function __construct(array $log){
    $this->data = $log;
  }
  
  public function size() : int{
    return count($this->data);
  }
  
  public function render($callback){
    foreach($this->data as $data){
      call_user_func(
        $callback,
        $data->created,
        call_user_func_array("sprintf", array_merge([$data->message], json_decode($data->arg)))
        );
    }
  }
}