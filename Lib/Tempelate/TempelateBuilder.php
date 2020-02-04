<?php
namespace Lib\Tempelate;

interface TempelateBuilder{
  public function build(TempelateParser $parser) : TempelateNode;
}