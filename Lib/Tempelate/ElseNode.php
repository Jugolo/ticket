<?php
namespace Lib\Tempelate;

class ElseNode implements TempelateNode{
  public function toCode() : string{
    return "}else{";
  }
}