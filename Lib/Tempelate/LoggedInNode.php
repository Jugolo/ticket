<?php
namespace Lib\Tempelate;

class LoggedInNode implements TempelateNode{
  public function toCode() : string{
    return "defined('user')";
  }
}