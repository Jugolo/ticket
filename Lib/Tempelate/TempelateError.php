<?php
namespace Lib\Tempelate;

class TempelateError{
  private $xml;
  
  public function __construct(\SimpleXMLElement $xml){
    $this->xml = $xml;
  }
  
  public function hasAccessDenid() : bool{
    return $this->xml->accessdenid !== null;
  }
  
  public function accessdenid() : string{
    return (string)$this->xml->accessdenid["name"];
  }
  
  public function hasNotFound() : bool{
    return $this->xml->notfound !== null;
  }
  
  public function notfound() : string{
    return (string)$this->xml->notfound["name"];
  }
}