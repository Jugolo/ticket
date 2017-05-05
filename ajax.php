<?php
header('Content-Type: application/json');
$ajax = [];

function updateAjax(&$ajax){
  if(defined("user")){
    $ajax["unread_ticket"] = unreadtickets();
  }
}

switch($_GET["_ajax"]){
  case "update":
    updateAjax($ajax);
  break;
  default:
    html_error("Unknown ajax request");
}

if(html_error_count() != 0){
  $ajax["error"] = $_SESSION["error"];
  unset($_SESSION["error"]);
}

exit(json_encode($ajax));
