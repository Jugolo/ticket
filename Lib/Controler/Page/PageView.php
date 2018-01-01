<?php
namespace Lib\Controler\Page;

use Lib\Tempelate;
use Lib\Page;

interface PageView{
  function loginNeeded() : string;
  function identify() : string;
  function access() : array;
  function body(Tempelate $tempelate, Page $page);
}