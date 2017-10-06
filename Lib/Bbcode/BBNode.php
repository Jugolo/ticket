<?php
namespace Lib\Bbcode;

interface BBNode{
  public function appendNode(BBNode $node);
  public function toHtml() : string;
  public function tag() : string;
}