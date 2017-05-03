<?php
//define("logo", "test.jpg");
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_TABLE", "ticket");
session_start();

include "lib/tempelate.php";

function salt_password(string $password, string $salt){
  return sha1($salt.$password.$salt);
}

function doLogin(){
  $error = html_error_count();
  if(empty($_POST["username"]) || !trim($_POST["username"])){
    html_error("Missing username");
  }
  
  if(empty($_POST["password"]) || !trim($_POST["password"])){
    html_error("Missing password");
  }
  
  if(html_error_count() == $error){
    $db = database();
    $row = $db->query("SELECT `id`, `password`, `salt`, `isActivatet` FROM `user` WHERE `username`='".$db->real_escape_string($_POST["username"])."'")->fetch_assoc();
    if($row){
      if(salt_password($_POST["password"], $row["salt"]) == $row["password"]){
        if($row["isActivatet"] == 1){
          $_SESSION["uid"] = $row["id"];
        }else{
          html_error("You account is not activatet yet!");
        }
      }else{
        html_error("Faild to find username or/and password");
      }
    }else{
      html_error("Failed to find username or/and password");
    }
  }
  header("location: #");
  exit;
}

function doCreate(){
  $error = html_error_count();
  if(empty($_POST["create_username"]) || !trim($_POST["create_username"])){
    html_error("Missing username");
  }
  
  $p = $r = true;
  
  if(empty($_POST["create_password"]) || !trim($_POST["create_password"])){
    html_error("Missing password");
    $p = false;
  }
  
  if(empty($_POST["repeat_password"]) || !trim($_POST["repeat_password"])){
    html_error("Missing repeat password");
    $r = false;
  }
  
  if($p && $r && $_POST["repeat_password"] != $_POST["create_password"]){
    html_error("The two password is not the same");
  }
  
  if(empty($_POST["email"]) || !trim($_POST["email"])){
    html_error("Missing email");
  }
  
  if($error == html_error_count()){
    $db = database();
    $info = $db->query("SELECT `id`
                        FROM `user`
                        WHERE `username`='".$db->real_escape_string($_POST["create_username"])."'
                        OR `email`='".$db->real_escape_string($_POST["email"])."'")->fetch_assoc();
    
    if(!$info){
      $salt = randomString(200);
      $gid = getStandartGroup()["id"];
      $db->query("INSERT INTO `user` (
        `username`,
        `password`,
        `email`,
        `salt`,
        `isActivatet`,
        `groupid`
      ) VALUES (
        '".$db->real_escape_string($_POST["create_username"])."',
        '".$db->real_escape_string(salt_password($_POST["create_password"], $salt))."',
        '".$db->real_escape_string($_POST["email"])."',
        '".$db->real_escape_string($salt)."',
        0,
        ".$gid."
      );");
       mail($_POST["email"], "Please activate you new account", "Hallo ".$_POST["create_username"]."
You has just create an account and to be sure this email is belong to you, you need to confirm it with visit the link below.
If you dont has create an account you dont need to do anythink. 
".geturl()."?salt=".urlencode($salt)."&email=".urlencode($_POST["email"])."
Best regards from us", implode("\r\n", [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=utf8",
        "from:support@".$_SERVER["SERVER_NAME"],
        ]));
      html_okay("You account is created. Please look in you email for activate it");
    }else{
      html_error("The username or/and email is taken.");
    }
  }
  
  header("location: #");
  exit;
}

function doActivate(){
  $db = database();
  $info = $db->query("SELECT `id`
                      FROM `user`
                      WHERE `email`='".$db->real_escape_string($_GET["email"])."'
                      AND `salt`='".$db->real_escape_string($_GET["salt"])."'
                      AND `isActivatet`=0")->fetch_assoc();
  if(!$info){
    html_error("Could not find the account. Maby it is already activated?");
  }else{
    $db->query("UPDATE `user` SET `isActivatet`=1 WHERE `id`=".$info["id"]);
    html_okay("The account is now activated and you can now login");
  }
  
  header("location: ?view=front");
  exit;
}

function controleAuth(){
  if(!empty($_POST["login"])){
    doLogin();
  }elseif(!empty($_POST["createaccount"])){
    doCreate();
  }elseif(!empty($_GET["salt"]) && !empty($_GET["email"])){
    doActivate();
  }
}

function controleAutoLogin(){
  if(empty($_SESSION["uid"])){
    controleAuth();
    return;
  }
  
  $db = database();
  $info = $db->query("SELECT * FROM `user` WHERE `id`='".$db->real_escape_string($_SESSION["uid"])."'")->fetch_assoc();
  if(!$info){
    unset($_SESSION["uid"]);
    controleAuth();
    return;
  }
  
  define("user", $info);
}

function updateUserGroup(array $user, $id){
  $db = database();
  $db->query("UPDATE `user` SET `groupid`='".(int)$id."' WHERE `id`='".(int)$user["id"]."'");
}

function getUsergroup(int $id){
  static $buffer = [];
  if(!array_key_exists($id, $buffer)){
   $buffer[$id] = database()->query("SELECT * FROM `group` WHERE `id`='".(int)$id."'")->fetch_assoc();  
  }
  return $buffer[$id];
}

function randomString(int $length) : string{
  $buffer = "";
  for($i=0;$i<$length;$i++){
    $buffer .= chr(mt_rand(33, 126));
  }
  return $buffer;
}

function notfound(){
  header("HTTP/1.0 404 Not Found");
  echo "The request page was not found....";
}

function database(){
  static $db = null;
  if($db == null){
    if(!defined("DB_HOST") || !defined("DB_USER") || !defined("DB_PASS") || !defined("DB_TABLE")){
      exit("Missing database setting!");
    }
    
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_TABLE);
    if(!$db->connection_error){
     $db->set_charset("utf8"); 
    }
  }
  return $db;
}

controleAutoLogin();

function show_front(){
  if(defined("user") && !empty($_GET["logout"]) && $_GET["logout"] == session_id()){
    session_destroy();
    header("location: ?view=front");
    exit;
  }
  if(!file_exists("temp/front.inc")){
    echo "<h3>Hallo and welcomment to our site</h3><br>
    If you are server admin for this server so please read this:<br>
    Please make you own front page text. This is done to create './temp/front.inc' and make a page there will be shown here<br>
    Best regards the devolper";
  }else{
    include "temp/front.inc";
  }
}

function geturl(){
  return $_SERVER["HTTP_ORIGIN"].$_SERVER["SCRIPT_NAME"];
}

function getStandartGroup(){
  return database()->query("SELECT * FROM `group` WHERE `isStandart`='1'")->fetch_assoc();
}

function select_to(){
  if(!empty($_GET["to"])){
    notfound();
    return;
  }
  $query = database()->query("SELECT `id`, `name` FROM `catogory` WHERE `open`='1'");
  if($query->num_rows === 0){
    echo "<h3>No catgory is avarible.</h3>";
    return;
  }
  if($query->num_rows === 1){
    //there are only one item wee select this for the user
    header("location: ?view=apply&to=".$query->fetch_assoc()["id"]);
    exit;
  }
  echo "<form method='get' action='?view=apply'>";
  $options = "";
  while($row = $query->fetch_assoc()){
    $options .= "<option value='".$row["id"]."'>".$row["name"]."</option>";
  }
  echo two_container("Select to", "<select name='to'>{$options}</select>");
  echo "<input type='hidden' name='view' value='apply'>";
  echo "<input type='submit' value='Select'>";
  echo "</form>";
}

function get_to_data(){
  $db = database();
  $query = $db->query("SELECT * FROM `catogory` WHERE `id`='".$db->real_escape_string($_GET["to"])."'");
  if($query->num_rows == 0){
    return null;
  }
  return $query->fetch_assoc();
}

function controle_apply(array $data){
  $db = database();
  $errcount = 0;
  $query = $db->query("SELECT * FROM `category_item` WHERE `cid`='".$data["id"]."'");
  $sqlBuffer = [];
  while($row = $query->fetch_assoc()){
    if(!array_key_exists($row["id"], $_POST)){
      html_error("Missing '".htmlentities($row["text"])."'");
      $errcount++;
    }elseif($row["type"] != 3 && !trim($_POST[$row["id"]])){
      html_error("Missing '".htmlentities($row["text"])."'");
      $errcount++;
    }elseif($row["type"] == 3){
      $count = count(($option = explode(",", $row["placeholder"])))-1;
      $value = intval($_POST[$row["id"]]);
      if($value < 0 || $value > $count){
        html_error("Missing '".htmlentities($row["text"])."'");
        $errcount++;
      }else{
        $sqlBuffer[] = "INSERT INTO `ticket_value` (`hid`, `text`, `type`, `value`) VALUES (%%hid%%, '".$db->real_escape_string($row["text"])."', '".$row["type"]."', '".$db->real_escape_string($option[$value])."')";
      }
    }else{
      $sqlBuffer[] = "INSERT INTO `ticket_value` (`hid`, `text`, `type`, `value`) VALUES(%%hid%%, '".$db->real_escape_string($row["text"])."', '".$row["type"]."', '".$db->real_escape_string($_POST[$row["id"]])."')";
    }
  }
  if($errcount !== 0){
    header("location: ?view=apply&to=".$data["id"]);
    exit;
  }else{
     $db->query("INSERT INTO `ticket` (`cid`, `uid`, `created`, `changed`) VALUES ('".$data["id"]."', '".user["id"]."', NOW(), NOW())");
     $id = $db->insert_id;
     if($db->multi_query(str_replace("(%%hid%%,", "('".$id."',", implode(";\r\n", $sqlBuffer)))){
       while($db->more_results() && $db->next_result()){
         $db->store_result();
       }
       mail(user["email"], "You ticket is created", "Hallo ".user["username"]."
You ticket is created and wee will take care about it. Please controle the ticket often becuse wee can ask you about somthink
there can be importen to this case", implode("\r\n", [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=utf8",
        "from:support@".$_SERVER["SERVER_NAME"],
        ]));
       html_okay("You ticket is saved");
       header("location: ?view=ticket&ticket_id=".$id);
       exit;
     }else{
       //sql get wrong
       $db->query("DELETE FROM `ticket` WHERE `id`='".$id."'");
       html_error("Sorry we could not save you application");
       header("location: ?view=apply&to=".$data["id"]);
       exit;
     }
  }
}

function show_apply(){
  if(!defined("user")){
    notfound();
    return;
  }
  if(empty($_GET["to"]) || !($data = get_to_data())){
    select_to();
    return;
  }
  if(!empty($_GET["done"])){
    controle_apply($data);
  }
  echo "<form method='post' action='?view=apply&to=".$data["id"]."&done=true'>";
  $db = database();
  $query = $db->query("SELECT * FROM `category_item` WHERE `cid`='".$data["id"]."'");
  while($row = $query->fetch_assoc()){
    if($row["type"] == 1){
      echo two_container($row["text"], "<input type='text' name='".$row["id"]."' placeholder='".htmlentities($row["placeholder"])."'>");
    }elseif($row["type"] == 2){
      echo "<div class='center'>".$row["text"]."</div>";
      echo "<textarea class='apply' name='".$row['id']."' placeholder='".htmlentities($row["placeholder"])."'></textarea>";
    }elseif($row["type"] == 3){
      $item = explode(",", $row["placeholder"]);
      $options = "";
      for($i=0;$i<count($item);$i++){
        $options .= "<option value='".$i."'>".trim($item[$i])."</option>";
      }
      echo two_container($row["text"], "<select name='{$row["id"]}'>{$options}</select>");
    }
  }
  echo "<input type='submit' value='Submit'>";
  echo "</form>";
}

function show_tickets(){
  if(!defined("user")){
    notfound();
    return;
  }
  $db = database();
  $query = $db->query("SELECT ticket.id, catogory.name, ticket.created, ticket.changed, ticket_track.visit
  FROM `ticket`
  LEFT JOIN `catogory` ON catogory.id=ticket.cid
  LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid=ticket.uid
  WHERE ticket.uid='".user["id"]."'");
  
  if($query->num_rows !== 0){
    echo "<h3>Yours ticket</h3>";
    tickets_overview_open(false);
    while($row = $query->fetch_assoc()){
      tickets_overview_context($row, false);
    }
    tickets_overview_close();
  }
  $group = getUsergroup(user["groupid"]);
  if($group["showTicket"] == "1"){
    echo "<hr>";
    echo "<h3>Other ticket</h3>";
    tickets_overview_open(true);
    $query = $db->query("SELECT ticket.id, catogory.name, ticket.created, user.username, ticket.changed, ticket_track.visit
    FROM `ticket`
    LEFT JOIN `catogory` ON catogory.id=ticket.cid
    LEFT JOIN `user` ON user.id=ticket.uid
    LEFT JOIN `ticket_track` ON ticket_track.tid=ticket.id AND ticket_track.uid='".user["id"]."'
    WHERE ticket.uid<>'".user["id"]."'");
    
    while($row = $query->fetch_assoc()){
      tickets_overview_context($row, true);
      echo "<div class='ticket_overview'>";
       echo "<span class='category'><a href='?view=ticket&ticket_id=".$row["id"]."'>".$row["name"]."</a></span> ";
       echo "<span class='nick'>".$row["username"]."</span> ";
       echo "<span class='time'>".$row["created"]."</span>";
      echo "</div>";
    }
    tickets_overview_close();
  }
}

function comment_ticket(array $ticket){
  if($ticket["uid"] == user["id"]){
    $public = true;
  }else{
    $public = array_key_exists("public", $_POST);
  }
  
  $db = database();
  $db->query("INSERT INTO `comment` (
    `tid`,
    `uid`,
    `public`,
    `created`,
    `message`
  ) VALUES (
    '".$ticket["id"]."',
    '".user["id"]."',
    '".($public ? "1" : "0")."',
    NOW(),
    '".$db->real_escape_string($_POST["comment"])."'
  );");
  $db->query("UPDATE `ticket` SET `changed`=NOW() WHERE `id`='".$ticket["id"]."'");
  
  if(!$public){
    //this is not a public message and to avoid the user is notice about it wee do ekstra controle here.
    $info = $db->query("SELECT `visit` FROM `ticket_track` WHERE `tid`='".$ticket["id"]."' AND `uid`='".$ticket["uid"]."'")->fetch_assoc();
    if(strtotime($info["visit"]) >= strtotime($ticket["changed"])){
      $db->query("UPDATE `ticket_track` SET `visit`=NOW() WHERE `tid`='".$ticket["id"]."' AND `uid`='".$ticket["uid"]."'");
    }
  }
  
  header("location:?view=ticket&ticket_id=".$ticket["id"]);
  exit;
}

function show_ticket(){
  if(!defined("user") || empty($_GET["ticket_id"])){
    notfound();
    return;
  }
  //wee get the user grup.... (me)
  $group = getUsergroup(user["groupid"]);
  $db = database();
  $query = $db->query("SELECT ticket.*, catogory.name, user.username
  FROM `ticket`
  LEFT JOIN `catogory` ON catogory.id=ticket.cid
  LEFT JOIN `user` ON user.id=ticket.uid
  WHERE ticket.id='".$db->real_escape_string($_GET["ticket_id"])."'");
  $row = $query->fetch_assoc();
  if(!$row){
    notfound();
    return;
  }
  
  if($row["uid"] != user["id"] && $group["showTicket"] == 0){
    notfound();
    return;
  }
  
  if(!empty($_POST["comment"]) && trim($_POST["comment"])){
    comment_ticket($row);
  }
  
  $db->query(($test = "UPDATE `ticket_track` SET `visit`=NOW() WHERE `tid`='".$row["id"]."' AND `uid`='".user["id"]."'"));
  if($db->affected_rows == 0){
    $db->query("INSERT INTO `ticket_track` (
      `tid`,
      `uid`,
      `visit`
    ) VALUES (
      '".$row["id"]."',
      '".user["id"]."',
      NOW()
    );");
  }
  
  echo "<div id='ticket'>";
  echo two_container("From", htmlentities($row["username"]));
  echo two_container("Created", $row["created"]);
  $query = $db->query("SELECT * FROM `ticket_value` WHERE `hid`='".$row["id"]."'");
  while($value = $query->fetch_assoc()){
    if($value["type"] == 2){
      echo "<div>";
        echo "<div class='center'>".htmlentities($value["text"], ENT_QUOTES | ENT_SUBSTITUTE)."</div>";
        echo "<div>".nl2br(htmlentities($value["value"]))."</div>";
      echo "</div>";
    }else{
      echo two_container($value["text"], htmlentities($value["value"]));
    }
  }
  echo "</div>";
  echo "<hr>";
  echo "<h3>Comments</h3>";
  $query = $db->query("SELECT comment.*, user.username 
  FROM `comment`
  LEFT JOIN `user` ON user.id=comment.uid
  WHERE comment.tid='".$row["id"]."'".($row["uid"] == user["id"] ? "
  AND comment.public='1'" : "")."
  ORDER BY comment.id ASC");
  if($query->num_rows !== 0){
    echo "<div id='comments'>";
    while($comment = $query->fetch_assoc()){
      echo "<div class='comment'>";
        echo "<div class='infomation'>";
          echo two_container("From", htmlentities($comment["username"]));
          if($row["uid"] != user["id"]){
            echo two_container("Is public", $comment["public"] == 1 ? "Yes" : "No");
          }
          echo two_container("Created", $comment["created"]);
        echo "</div>";
        echo "<div class='message'>".nl2br(htmlentities($comment["message"]))."</div>";
        echo "<div class='clear'></div>";
      echo "</div>";
    }
    echo "</div>";
  }else{
    echo "<h3>No comments yet</h3>";
  }
  echo "<hr>";
  echo "<form method='post' action='#'>";
  echo "<div>";
    echo "<div class='title'>Write new comments</div>";
    echo "<div><textarea name='comment'></textarea></div>";
    if($row["uid"] != user["id"]){
      echo "<div>Public <input type='checkbox' class='leave' name='public' value='1'></div>";
    }
    echo "<div><input type='submit' value='Comment this ticket'></div>";
  echo "</div>";
  echo "</form>";
}

function show_users(){
  if(!defined("user") || !showUserMenu()){
    notfound();
    return;
  }
  $group = getUsergroup(user["groupid"]);
  $query = database()->query("SELECT `id`, `username` FROM `user`");
  while($row = $query->fetch_assoc()){
    echo two_container(htmlentities($row["username"]), ($group["changeGroup"] == 1 ? "<a href='?view=changegroup&uid=".$row["id"]."'>Change group</a>" : ""));
  }
}

function show_changeusergroup(){
 if(!defined("user") || empty($_GET["uid"])){
   notfound();
   return;
 }
  
  $group = getUsergroup(user["groupid"]);
  if($group["changeGroup"] != 1){
    notfound();
    return;
  }
  
  $db = database();
  //wee found the user now
  $user = $db->query("SELECT `id`, `username`, `groupid` FROM `user` WHERE `id`='".$db->real_escape_string($_GET["uid"])."'")->fetch_assoc();
  if(!$user){
    notfound();
    return;
  }
  
  if(!empty($_GET["gid"])){
    updateUserGroup($user, $_GET["gid"]);
    html_okay("The users group is now updated");
    header("location: ?view=changegroup&uid=".$_GET["uid"]);
    exit;
  }
  
  echo "<h3>Change group for ".htmlentities($user["username"])."</h3>";
  if(user["id"] == $user["id"]){
   echo "<h3 class='notokay'>You looking of you owen membership of this group!</h3>"; 
  }
  $query = $db->query("SELECT * FROM `group`");
  while($row = $query->fetch_assoc()){
    $options = [];
    if($row["id"] == $user["groupid"]){
      $options["tag2class"] = "notokay";
      $two = "Chose";
    }else{
      $two = "<a href='?view=changegroup&uid=".$user["id"]."&gid=".$row["id"]."' class='okay'>Chose</a>";
    }
    echo two_container(htmlentities($row["name"]), $two, $options);
  }
}

function show_handleGroup(){
  if(!defined("user")){
    notfound();
    return;
  }
  
  $group = getUsergroup(user["groupid"]);
  if($group["handleGroup"] != 1){
    notfound();
    return;
  }
  
  $db = database();
  
  if(!empty($_GET["gid"])){
    $g = getStandartGroup();
    if($g["id"] == $_GET["gid"]){
      html_error("The group can`t be deleted becuse it is standart group!");
      header("location: ?view=handleGroup");
      exit;
    }
    $db->query("UPDATE `user` SET `groupid`='".(int)$g["id"]."' WHERE `groupid`='".$db->real_escape_string($_GET["gid"])."'");
    $db->query("DELETE FROM `group` WHERE `id`='".$db->real_escape_string((int)$_GET["gid"])."'");
    html_okay("The group is delteded");
    header("location: ?view=handleGroup");
    exit;
  }
  
  if(!empty($_POST["name"]) && trim($_POST["name"])){
    $db->query("INSERT INTO `group` (
      `name`,
      `isStandart`,
      `showTicket`,
      `changeGroup`,
      `handleGroup`
    ) VALUES (
      '".$db->real_escape_string($_POST["name"])."',
      '0',
      '0',
      '0',
      '0'
    );");
    html_okay("The group is created");
    header("location: ?view=access&gid=".$db->insert_id);
    exit;
  }
  
  $query = $db->query("SELECT `id`, `name` FROM `group`");
  while($row = $query->fetch_assoc()){
    echo two_container($row["name"], "<a href='?view=handleGroup&gid=".$row["id"]."'>Delete group</a> <a href='?view=access&gid=".$row["id"]."'>Change access</a>");
  }
  
  echo "<hr>";
  echo "<form method='post' action='?view=handleGroup'>";
  echo "<h3>Create new group</h3>";
  echo two_container("Name", "<input type='text' name='name' placeholder='Fill the new groups name'>");
  echo "<div>";
    echo "<input type='submit' value='Create group'>";
  echo "</div>";
  echo "</form>";
}

function update_access(array $group){
  $update = [];
  if(!empty($_POST["showticket"]) && $group["showTicket"] == 0){
    $update["showTicket"] = "1";
  }elseif(empty($_POST["showticket"]) && $group["showTicket"] == 1){
    $update["showTicket"] = "0";
  }
  
  if(!empty($_POST["changegroup"]) && $group["changeGroup"] == 0){
    $update["changeGroup"] = "1";
  }elseif(empty($_POST["changegroup"]) && $group["changeGroup"] == 1){
    $update["changeGroup"] = "0";
  }
  
  if(!empty($_POST["handleGroup"]) && $group["handleGroup"] == 0){
    $update["handleGroup"] = "1";
  }elseif(empty($_POST["handleGroup"]) && $group["handleGroup"] == 1){
    $update["handleGroup"] = "0";
  }
  
  if(!empty($_POST["handleTickets"]) && $group["handleTickets"] == 0){
    $update["handleTickets"] = "1";
  }elseif(empty($_POST["handleTickets"]) && $group["handleTickets"] == 1){
    $update["handleTickets"] = "0";
  }
  
  if(count($update) > 0){
    $sql = [];
    foreach($update as $key => $value){
      $sql[] = "`".$key."`='".intval($value)."'";
    }
    database()->query("UPDATE `group` SET ".implode(",", $sql)." WHERE `id`='".$group["id"]."'");
    html_okay("Access updated");
  }else{
    html_okay("No update detected");
  }
  
  header("location: ?view=access&gid=".$group["id"]);
  exit;
}

function show_access(){
  if(!defined("user") || empty($_GET["gid"])){
    notfound();
    return;
  }
  
  $ugroup = getUsergroup(user["groupid"]);
  $group = $ugroup["id"] == $_GET["gid"] ? $ugroup : getUsergroup($_GET["gid"]);
  if($ugroup["handleGroup"] != 1){
    notfound();
    return;
  }
  
  if(!empty($_POST["update"])){
    update_access($group);
  }
  
  echo "<h3>Change access for {$group["name"]}</h3>";
  echo "<form method='post' action='#'>";
  echo two_container("Show other tickets", "<input type='checkbox' name='showticket'".($group["showTicket"] == 1 ? " checked" : "").">");
  echo two_container("Change group", "<input type='checkbox' name='changegroup'".($group["changeGroup"] == 1 ? " checked" : "").">");
  echo two_container("Handle group", "<input type='checkbox' name='handleGroup'".($group["handleGroup"] == 1 ? " checked" : "").">");
  echo two_container("Admin ticket", "<input type='checkbox' name='handleTickets'".($group["handleTickets"] == 1 ? " checked" : "").">");
  echo "<div><input type='submit' name='update' value='Update access'></div>";
  echo "</form>";
}

function deleteCategory(){
  if(empty($_GET["delete"])){
    return;
  }
  
  //find all ticket here 
  $db = database();
  $query = $db->query("SELECT `id` FROM `ticket` WHERE `cid`='".(int)$_GET["delete"]."'");
  while($row = $query->fetch_assoc()){
    $db->query("DELETE FROM `ticket_value` WHERE `hid`='{$row["id"]}'");
    $db->query("DELETE FROM `comment` WHERE `tid`='{$row["id"]}'");
    $db->query("DELETE FROM `ticket_track` WHERE `tid`='{$row["id"]}'");
  }
  
  $db->query("DELETE FROM `ticket` WHERE `cid`='".(int)$_GET["delete"]."'");
  $db->query("DELETE FROM `category_item` WHERE `cid`='".(int)$_GET["delete"]."'");
  $db->query("DELETE FROM `catogory` WHERE `id`='".(int)$_GET["delete"]."'");
  html_okay("Category is now deletede");
  header("location: ?view=handleTickets");
  exit;
}

function createCategory(){
  if(!empty($_POST["name"]) && trim($_POST["name"])){
    $db = database();
    $db->query("INSERT INTO `catogory` (
      `name`,
      `open`
    ) VALUES (
      '".$db->real_escape_string($_POST["name"])."',
      '0'
    );");
    html_okay("Category is now created");
    header("location: ?view=handleTickets&tid=".$db->insert_id);
    exit;
  }
  echo "<form method='POST' action='#'>";
  echo two_container("Name", "<input type='text' name='name'>");
  echo "<input type='submit' value='Create category'>";
  echo "</form>";
}

function createCatItem(){
  $error = 0;
  if(empty($_POST["name"]) || !trim($_POST["name"])){
    html_error("Missing item name");
    $error++;
  }
  
  if(empty($_POST["type"])){
    html_error("Missing type");
    $error++;
  }elseif(!is_numeric($_POST["type"])){
    html_error("Unexpected type value type");
    $error++;
  }elseif($_POST["type"] < 1 || $_POST["type"] > 3){
    html_error("Unexpected type range");
    $error++;
  }
  
  if(empty($_POST["value"]) || !trim($_POST["value"])){
    html_error("Missing item placeholder");
    $error++;
  }
  
  if($error === 0){
    $db = database();
    $db->query("INSERT INTO `category_item` (
      `cid`,
      `type`,
      `text`,
      `placeholder`
    ) VALUES (
      '".$db->real_escape_string($_GET["tid"])."',
      '".$db->real_escape_string($_POST["type"])."',
      '".$db->real_escape_string($_POST["name"])."',
      '".$db->real_escape_string($_POST["value"])."'
    )");
    html_okay("item is created");
  }
  header("location: #");
  exit;
}

function deleteCatItem(){
  $db = database();
  $db->query("DELETE FROM `category_item` WHERE `id`='".$db->real_escape_string($_GET["delete"])."'");
  html_okay("The item is now deleted");
  header("location: ?view=handleTickets&tid=".$_GET["tid"]);
  exit;
}

function adminTicket(){
  $db = database();
  $row = $db->query("SELECT * FROM `catogory` WHERE `id`='".$db->real_escape_string($_GET["tid"])."'")->fetch_assoc();
  if(!$row){
    html_error("Unknown category");
    header("location: ?view=handleTickets");
    exit;
  }
  
  if(!empty($_POST["append"])){
    createCatItem();
  }
  
  if(!empty($_GET["delete"])){
    deleteCatItem();
  }
  ?>
  <fieldset>
    <form method='post' action='#'>
    <legend>Append a new field</legend>
    Name <input type='text' name='name' class='leave' placeholder='Type here the name of the field'>
    Type <select name='type' class='leave' onchange='selectchange(this)'>
    <option value='1'>Input</option>
    <option value='2'>Textarea</option>
    <option value='3'>Select</option>
    </select>
    Value <input type='text' name='value' class='leave' id='catvalue' placeholder='Type here the placeholder'>
    <input type='submit' name='append' value='Create field'>
    </form>
  </fieldset>
  <script>
    function selectchange(obj){
      document.getElementById("catvalue").placeholder = obj.value == 3 ? "Seperate the option by comma" : "Type here the placeholder";
    }
  </script>
  <?php
  echo "<table class='style'>";
  $query = $db->query("SELECT * FROM `category_item` WHERE `cid`='".(int)$_GET["tid"]."'");
  if($query->num_rows == 0){
    echo "<tr><th>No item found</th></tr>";
  }else{
    ?>
    <tr>
      <th>Name</th>
      <th>Type</th>
      <th>Placeholder</th>
      <th>Option</th>
    </tr>
    <?php
    while($row = $query->fetch_assoc()){
      echo "<tr>";
        echo "<td>{$row["text"]}</td>";
        echo "<td>";
        switch($row["type"]){
          case 1:
            echo "Input";
          break;
          case 2:
            echo "Textarea";
          break;
          case 3:
            echo "Select";
          break;
        }
        echo "</td>";
        echo "<td>{$row["placeholder"]}</td>";
        echo "<td><a href='?view=handleTickets&delete={$row["id"]}&tid={$_GET["tid"]}'>Delete</a></td>";
      echo "</tr>";
    }
  }
  echo "</table>";
}

function show_handleTickets(){
  if(!defined("user") || getUsergroup(user["groupid"])["handleTickets"] == "0"){
    notfound();
    return;
  }
  
  if(!empty($_GET["tid"])){
    adminTicket();
    return;
  }
  
  if(!empty($_GET["delete"])){
    deleteCategory();
  }
  
  if(!empty($_GET["create"])){
    createCategory();
    return;
  }
  
  if(!empty($_GET["open"]) && !empty($_GET["id"])){
    $db = database();
    $db->query("UPDATE `catogory` SET `open`='".($_GET["open"] == "true" ? "1" : "0")."' WHERE `id`='".$db->real_escape_string($_GET["id"])."'");
    html_okay("The open status is updated");
    header("location: ?view=handleTickets");
    exit;
  }
  
  echo "<a href='?view=handleTickets&create=true'>Create new category</a>";
  echo "<hr>";
  
  $query = database()->query("SELECT `id`, `name`, `open` FROM `catogory`");
  while($row = $query->fetch_assoc()){
    echo two_container(
      "<a href='?view=handleTickets&tid={$row["id"]}'>{$row["name"]}</a></span>",
      "<a href='?view=handleTickets&delete={$row["id"]}'>Delete</a> <a href='?view=handleTickets&open=".($row["open"] == 1 ? "false" : "true")."&id={$row["id"]}'>".($row["open"] == 1 ? "Close" : "Open")."</a>"
      );
  }
}

function getContext(){
  switch((empty($_GET["view"]) ? "front" : $_GET["view"])){
    case "front":
      show_front();
    break;
    case "apply":
      show_apply();
    break;
    case "tickets":
      show_tickets();
    break;
    case "ticket":
      show_ticket();
    break;
    case "users":
      show_users();
    break;
    case "changegroup":
      show_changeusergroup();
    break;
    case "handleGroup":
      show_handleGroup();
    break;
    case "access":
      show_access();
    break;
    case "handleTickets":
      show_handleTickets();
    break;
    default:
      notfound();
  }
}

function showUserMenu(){
  $group = getUsergroup(user["groupid"]);
  return $group["changeGroup"] == 1;
}

function unreadtickets(){
  $sql = "SELECT COUNT(ticket.id) AS id
          FROM `ticket`
          LEFT JOIN `ticket_track` ON ticket.id=ticket_track.tid AND ticket_track.uid='".user["id"]."'
          WHERE (ticket_track.tid IS NULL OR ticket_track.tid IS NOT NULL AND ticket_track.visit<ticket.changed)";
  
  $group = getUsergroup(user["groupid"]);
  $db = database();
  if($group["showTicket"] == "0"){
    $sql .= " AND ticket.uid='".$db->real_escape_string(user["id"])."'";
  }
  
  return $db->query($sql)->fetch_assoc()["id"];
}

function getMenu(){
  echo "<div id='menu'>";
    echo "<table>";
      echo "<tr>";
        echo "<td><a href='?view=front'>Front page</a></td>";
    if(defined("user")){
      echo "<td><a href='?view=apply'>Apply</a></td>";
      $tc = unreadtickets();
      echo "<td><a href='?view=tickets'>Show tickets".($tc == 0 ? "" : " <span class='label'>".$tc."</span>")."</a></td>";
      if(showUserMenu()){
        echo "<td><a href='?view=users'>User</a></td>";
      }
      $group = getUsergroup(user["groupid"]);
      if($group["handleGroup"] == 1){
        echo "<td><a href='?view=handleGroup'>Group</a></td>";
      }
      if($group["handleTickets"] == "1"){
        echo "<td><a href='?view=handleTickets'>Tickets</a></td>";
      }
    }
      echo "</tr>";
    echo "</table>";
  echo "</div>";
}
ob_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet/less" type="text/css" href="style/main.less">
    <meta charset="UTF-8">
    <script type="text/javascript">
window.onerror = function(msg, url, line, col, error) {
   // Note that col & error are new to the HTML 5 spec and may not be 
   // supported in every browser.  It worked for me in Chrome.
   var extra = !col ? '' : '\ncolumn: ' + col;
   extra += !error ? '' : '\nerror: ' + error;

   // You can view the information in an alert to see things working like this:
   alert("Error: " + msg + "\nurl: " + url + "\nline: " + line + extra);

   // TODO: Report this error via ajax so you can keep track
   //       of what pages have JS issues

   var suppressErrorAlert = true;
   // If you return true, then error alerts (like in older versions of 
   // Internet Explorer) will be suppressed.
   return suppressErrorAlert;
};
</script>
    <script>
      less = {
        env: "development"
      };
    </script>
    <script src='less.js'></script>
    <script>
      function onload(){
        
      }
      
      function toggle(identify){
        var el = document.querySelectorAll(identify);
        for(var i=0;i<el.length;i++){
          el[i].style.display = el[i].offsetParent == null ? "block" : "none";
        }
      }<?php if(!defined("user")){ ?>
      
      function toggleLoginMethod(){
        var login = document.getElementsByClassName("login")[0];
        var create = document.getElementsByClassName("createAccount")[0];
        if(login.offsetParent == null){
          login.style.display = "block";
          create.style.display = "none";
        }else{
          login.style.display = "none";
          create.style.display = "block";
        }
      }<?php } ?>
    </script>
  </head>
  <body onload='onload();'>
    <div id="headmenu">
      <?php if(defined("user")){ ?>
      <div>
        <button onclick="toggle('#user_menu');"><?php echo htmlentities(user["username"]); ?></button>
        <div id='user_menu' class='menu'>
          <div class='center'>
            <a href='?view=front&logout=<?php echo urlencode(session_id()); ?>'>Logout</a>
          </div>
        </div>
      </div>
      <?php }else{ ?>
      <div>
        <button id='login' onclick='toggle("#login_menu");'>Login</button>
        <div id='login_menu' class='menu'>
          <form method='post' action='#'>
            <div class='login'>
              <h3>Login</h3>
              <div>
                <input type='text' name='username' placeholder='Username'>
              </div>
              <div>
                <input type='password' name='password' placeholder='Password'>
              </div>
              <div>
                <input type='submit' name='login' value='Login'>
              </div>
              <div class='right'>
                <a href='#' onclick='toggleLoginMethod();'>Or create account</a>
              </div>
            </div>
            <div class='createAccount'>
              <h3>Create account</h3>
              <div>
                <input type='text' name='create_username' placeholder='Username'>
              </div>
              <div>
                <input type='password' name='create_password' placeholder='Password'>
              </div>
              <div>
                <input type='password' name='repeat_password' placeholder='Repeat password'>
              </div>
              <div>
                <input type='email' name='email' placeholder='email'>
              </div>
              <div>
                <input type='submit' name='createaccount' value='Create account'>
              </div>
              <div class='right'>
                <a href='#' onclick='toggleLoginMethod()'>Or login</a>
              </div>
            </div>
          </form>
        </div>
      </div>
      <?php } ?>
    </div>
    <?php
    getHtmlError();
    getHtmlOkay();
    ?>
    <div id='container'>
      <?php if(defined("logo")){?>
      <div id='logo' style="background-image: url('<?php echo logo; ?>');">
      </div>
      <?php }
      getMenu(); ?>
      <div id='area'>
        <?php
        getContext();
        ?>
      </div>
      <div id='copy'>
        <a href='http://cowscript.dk'>CowScript</a>  2017 - All Rights Reserved
      </div>
    </div>
  </body>
</html>
<?php
ob_end_flush();
