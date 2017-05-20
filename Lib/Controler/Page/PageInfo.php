<?php
namespace Lib\Controler\Page;

interface PageInfo{
  function menuVisible() : bool;
  function pageVisible() : bool;
  function name() : string;
  function title() : string;
}
