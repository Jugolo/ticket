<?php
namespace Lib\Tempelate;

class GetScriptNode implements TempelateNode{
  public function toCode() : string{
    return "\$this->getScriptSources();";
  }
}