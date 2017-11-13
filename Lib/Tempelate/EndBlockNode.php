<?php
namespace Lib\Tempelate;

class EndBlockNode implements TempelateNode{
  public function toCode() : string{
    return "}";
  }
}