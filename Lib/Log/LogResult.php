<?php
namespace Lib\Log;

use Lib\Language\Language;
use Lib\Database\DatabaseResult;

class LogResult{
  private $data;
  
  public function __construct(DatabaseResult $log){
    $this->data = $log;
  }
  
  public function size() : int{
    return $this->data->count();
  }
  
  public function render($callback){
    if(!defined("LOG_LANG_LOADED")){
      define("LOG_LANG_LOADED", true);
      Language::load("log");
    }
    $this->data->fetch(function($created, $message, $arg) use($callback){
      call_user_func(
        $callback,
        $created,
        Language::get($message, json_decode($arg, true))
        );
    });
  }
}