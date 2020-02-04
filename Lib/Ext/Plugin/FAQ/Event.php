<?php
namespace Lib\Ext\Plugin\FAQ;

use Lib\Plugin\PluginInterface;
use Lib\Plugin\Event as PluginEvent;
use Lib\Access\AccessTreeBuilder;
use Lib\Language\Language;

class Event implements PluginInterface{
  public function getEvents() : array{
    return [
    "system.access.get" => [$this, "getAccess"]
    ];
  }
  
  public function getAccess(PluginEvent $event, AccessTreeBuilder $tree){
    $name = Language::get("FAQ");
    $tree->createCategory($name);
    $tree->setItem($name, "FAQ_CREATE", "FAQ_CREATE");
  }
}