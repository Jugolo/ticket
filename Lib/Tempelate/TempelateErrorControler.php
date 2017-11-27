<?php
namespace Lib\Tempelate;

class TempelateErrorControler{
  private $xml;
  
  public function __construct($xml){
    $this->xml = $xml;
  }
  
  public function accessdenid() : string{
    $name = $this->xml->accessdenid;
    if(!$name)
      return "";
    return (string)$name["name"];
  }
  
  public function notfound() : string{
    $name = $this->xml->notfound;
    if(!$name)
      return "";
    return (string)$name["name"];
  }
}