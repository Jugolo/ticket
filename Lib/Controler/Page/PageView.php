<?php
namespace Lib\Controler\Page;

use Lib\Tempelate;
use Lib\Page;
use Lib\User\User;

interface PageView{
  function loginNeeded() : string;
  function identify() : string;
  function access() : array;
  function body(Tempelate $tempelate, Page $page, User $user);
}
