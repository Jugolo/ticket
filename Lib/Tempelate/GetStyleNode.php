<?php
namespace Lib\Tempelate;

class GetStyleNode implements TempelateNode{
  public function toCode() : string{
    return "\$this->css->getStyleSources();";
  }
}