<?php
namespace Lib\Tempelate\Node;

use Lib\Tempelate\TempelateNode;

class TempelateEmptyNode implements TempelateNode{
  public function toString() : string{
    return "";
  }
}