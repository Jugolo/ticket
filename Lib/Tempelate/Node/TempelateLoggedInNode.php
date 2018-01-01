<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateLoggedInNode implements TempelateNode{
  public function toString() : string{
    return "defined('user')";
  }
}