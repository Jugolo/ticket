<?php
namespace Lib\Controler\Page;

use Lib\Tempelate;

interface PageView{
  function body(Tempelate $tempelate);
}