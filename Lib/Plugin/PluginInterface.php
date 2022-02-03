<?php
namespace Lib\Plugin;

interface PluginInterface{
  public function getEvents() : array;
}