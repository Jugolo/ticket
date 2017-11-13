<?php
namespace Lib\Tempelate;

interface TempelateNode{
  public function toCode() : string;
}