<?php
use Lib\Controler\Page\PageControler;
use Lib\Controler\Page\PageInfo;
use Lib\Database;
use Lib\Ext\Notification\Notification;
define("BASE", dirname(__FILE__)."/");
set_include_path(BASE);

header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

error_reporting(E_ALL);
//define('logo', '');
session_start();

set_error_handler(function($errno, $errstr, $errfile, $errline){
  if(defined("IN_SETUP")){
    return;
  }
  if(!defined("ERROR")){
    define("ERROR", true);
  }
  
  $db = Database::get();
  if(!$db){
    return;
  }
  $db->query("INSERT INTO `error` (
    `errno`,
    `errstr`,
    `errfile`,
    `errline`,
    `errtime`
  ) VALUES (
    '".$db->escape($errno)."',
    '".$db->escape($errstr)."',
    '".$db->escape($errfile)."',
    '".$db->escape($errline)."',
    NOW()
  );");
  
  if(defined("user")){
    $group = getUsergroup(user["groupid"]);
    if($group["showError"] == 1){
      html_error($errstr);
    }
  }
});

register_shutdown_function(function(){
  $error = error_get_last();
  if($error){
    $db = Database::get();
    $db->query("INSERT INTO `error` (
      `errno`,
      `errstr`,
      `errfile`,
      `errline`,
      `errtime`
    ) VALUES (
      '".$db->escape($error["type"])."',
      '".$db->escape($error["message"])."',
      '".$db->escape($error["file"])."',
      '".$db->escape($error["line"])."',
      NOW()
    )");
  }
  if(Database::isInit()){
    Database::get()->close();
  }
});

spl_autoload_register(function($class){
  if(!class_exists($class)){
    include str_replace("\\", "/", $class).".php";
  }
});

include "Lib/tempelate.php";
include "ajax.php";

if(!file_exists("config.php")){
  Lib\Setup\Main::controle();
}

include 'config.php';

function get_age(array $data){
  if(!empty($_POST["bd"]) && !empty($_POST["bm"]) && !empty($_POST["by"])){
    $db = Database::get();
    $db->query("UPDATE `user` SET 
                `birth_day`='".$db->escape(intval($_POST["bd"]))."',
                `birth_month`='".$db->escape(intval($_POST["bm"]))."',
                `birth_year`='".$db->escape(intval($_POST["by"]))."'
               WHERE `id`=".user["id"]);
    html_okay("You birth day is now saved");
    header("location: #");
    exit;
  }
  echo "<form method='post' action='#'>";
  echo "<h3>Please type your bith day to create the ticket to {$data["name"]}</h3>";
  echo two_container("Birth day", "<input type='number' name='bd'>");
  echo two_container("Birth month", "<input type='number' name='bm'>");
  echo two_container("Birth year", "<input type='number' name='by'>");
  echo "<input type='submit' value='Set you birth day'>";
  echo "</form>";
}

function controle_age(array $data) : bool{
  if(!user["birth_day"] || !user["birth_month"] || !user["birth_year"]){
    get_age($data);
    return false;
  }
  
  if($data["age"] > Lib\Age::calculate(user["birth_day"], user["birth_month"], user["birth_year"])){
    echo "<h3>Sorry you are to young to crate a ticket to {$data["name"]}</h3>";
    return false;
  }
  
  return true;
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
    $db = Database::get();
    $row = $db->query("SELECT `id`, `password`, `salt`, `isActivatet` FROM `user` WHERE `username`='".$db->escape($_POST["username"])."'")->fetch();
    if($row){
      if(Lib\User\Auth::salt_password($_POST["password"], $row->salt) == $row->password){
        if($row->isActivatet == 1){
          $_SESSION["uid"] = $row->id;
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
    $db = Database::get();
    $info = $db->query("SELECT `id`
                        FROM `user`
                        WHERE `username`='".$db->escape($_POST["create_username"])."'
                        OR `email`='".$db->escape($_POST["email"])."'")->fetch();
    
    if(!$info){
      $salt = Lib\User\Auth::randomString(200);
      $gid = getStandartGroup()["id"];
      $id = $db->query("INSERT INTO `user` (
        `username`,
        `password`,
        `email`,
        `salt`,
        `isActivatet`,
        `groupid`
      ) VALUES (
        '".$db->escape($_POST["create_username"])."',
        '".$db->escape(Lib\User\Auth::salt_password($_POST["create_password"], $salt))."',
        '".$db->escape($_POST["email"])."',
        '".$db->escape($salt)."',
        0,
        ".$gid."
      );");
      Notification::getNotification(function(string $name) use($db, $id){
        $db->query("INSERT INTO `notify_setting` VALUES ('{$id}', '{$db->escape($name)}');");
      });
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
      if(is_ajax()){
        ajax_var("create", true);
      }
    }else{
      if(is_ajax()){
        ajax_var("create", false);
      }
      html_error("The username or/and email is taken.");
    }
  }elseif(is_ajax()){
    ajax_var("create", false);
  }
  if(!is_ajax()){
    header("location: #");
    exit;
  }
}

function doActivate(){
  $db = Database::get();
  $info = $db->query("SELECT `id`
                      FROM `user`
                      WHERE `email`='".$db->escape($_GET["email"])."'
                      AND `salt`='".$db->escape($_GET["salt"])."'
                      AND `isActivatet`=0")->fetch();
  if(!$info){
    html_error("Could not find the account. Maby it is already activated?");
  }else{
    $db->query("UPDATE `user` SET `isActivatet`=1 WHERE `id`=".$info->id);
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
  
  $db = Database::get();
  $info = $db->query("SELECT * FROM `user` WHERE `id`='".$db->escape($_SESSION["uid"])."'")->fetch();
  if(!$info){
    unset($_SESSION["uid"]);
    controleAuth();
    return;
  }
  
  define("user", $info->toArray());
}

function updateUserGroup(Lib\Database\DatabaseFetch $user, $id){
  Database::get()->query("UPDATE `user` SET `groupid`='".(int)$id."' WHERE `id`='".(int)$user->id."'");
}

function getUsergroup(int $id){
  static $buffer = [];
  if(!array_key_exists($id, $buffer)){
   $buffer[$id] = Database::get()->query("SELECT * FROM `group` WHERE `id`='".(int)$id."'")->fetch()->toArray();  
  }
  return $buffer[$id];
}



function notfound(){
  header("HTTP/1.0 404 Not Found");
  echo "The request page was not found....";
}

controleAutoLogin();

if(defined("user") && user["id"] == "1"){
  if(file_exists("./Lib/Setup/Main.php")){
    Lib\Setup\Main::controle();
  }
}

function geturl(){
  return $_SERVER["HTTP_ORIGIN"].$_SERVER["SCRIPT_NAME"];
}

function getStandartGroup(){
  return Database::get()->query("SELECT * FROM `group` WHERE `isStandart`='1'")->fetch()->toArray();
}

function getContext(){
  $page = PageControler::getPage();
  if(!$page){
    trigger_error("Missing page viewer for: ".$_GET["view"], E_USER_ERROR);
    return;
  }
  $page->body();
}

function unreadtickets(){
  $sql = "SELECT COUNT(ticket.id) AS id
          FROM `ticket`
          LEFT JOIN `ticket_track` ON ticket.id=ticket_track.tid AND ticket_track.uid='".user["id"]."'
          WHERE (ticket_track.tid IS NULL OR ticket_track.tid IS NOT NULL AND ticket_track.visit<ticket.user_changed)";
  
  $group = getUsergroup(user["groupid"]);
  $db = database();
  if($group["showTicket"] == "0"){
    $sql .= " AND ticket.uid='".$db->real_escape_string(user["id"])."'";
  }
  
  return $db->query($sql)->fetch_assoc()["id"];
}

function getMenu(){
  echo "<div id='menu'".(defined("logo") ? "" : " class='nologo'").">";
  $table = new Table();
  $table->id = "menu_table";
  $table->newColummen();
  $table->th("<a href='?view=front'>Front</a>", true);
  PageControler::getPageInfo(function(PageInfo $page) use($table){
    if($page->name() !== "front" && $page->menuVisible()){
      $table->th("<a href='?view=".urlencode($page->name())."'>".htmlentities($page->title())."</a>", true);
    }
  });
  $table->output();
  echo "</div>";
}
if(is_ajax()){
  ajax_output();
}
ob_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet/less" type="text/css" href="style/main.less">
    <title>Ticket system</title>
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
    <script src='js/system.js'></script>
    <script>
      function onload(){
        <?php getHtmlError(); ?> 
        <?php getHtmlOkay(); ?> 
        CowTicket.init();
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
        
        CowTicket.createUser = function(){
          var username = document.getElementById("create_username");
          var password = document.getElementById("create_password");
          var rpassword = document.getElementById("repeat_password");
          var email = document.getElementById("email");
          var errorCount = 0;
          var r=true, p=true; 
          
          if(!username.value || username.value.trim().length == 0){
            this.error("Missing username");
            errorCount++;
          }
          
          if(!password.value || password.value.trim().length == 0){
            this.error("Missing password");
            errorCount++;
            p=false;
          }
          
          if(!rpassword.value || rpassword.value.trim().length == 0){
            this.error("Missing repeat password");
            errorCount++;
            r=false;
          }
          
          if(r && p && rpassword.value != password.value){
            this.error("The two password is not the same");
            errorCount++;
          }
          
          if(!email.value || email.value.trim().length == 0){
            this.error("Missing email");
            errorCount++;
          }
          
          if(errorCount == 0){
            this.ajax("null", {
              create_username : username.value,
              create_password : password.value,
              repeat_password : rpassword.value,
              email           : email.value,
              createaccount   : "true"
            }, function(a){
              if(a.create){
                toggle('#login_menu');
                toggleLoginMethod();
                username.value = "";
                password.value = "";
                rpassword.value = "";
                email.value = "";
              }
            });
          }
          return false;
        };
      }<?php } ?>
    </script>
  </head>
  <body onload='onload();'>
    <div id="headmenu">
      <?php if(defined("user")){ ?>
      <div class='head'>
        <button onclick="toggle('#user_menu');"><?php echo htmlentities(user["username"]); ?></button>
        <div id='user_menu' class='menu'>
          <div class='center'>
            <a href='?view=profile'>Profile</a>
          </div>
          <div class='center'>
            <a href='?view=front&logout=<?php echo urlencode(session_id()); ?>'>Logout</a>
          </div>
        </div>
      </div>
      <div class='head'>
        <div class='button' onclick="toggle('#notify_menu');">
          <div class='notifi_text'>
            Noticaftion
            <div class='notifi_count'>0</div>
          </div>
        </div>
        <div class='menu' id='notify_menu'>
             
        </div>
      </div>
      <?php }else{ ?>
      <div class='head'>
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
                <input type='text' name='create_username' id='create_username' placeholder='Username'>
              </div>
              <div>
                <input type='password' name='create_password' id='create_password' placeholder='Password'>
              </div>
              <div>
                <input type='password' name='repeat_password' id='repeat_password' placeholder='Repeat password'>
              </div>
              <div>
                <input type='email' name='email' id='email' placeholder='email'>
              </div>
              <div>
                <input type='submit' name='createaccount' value='Create account' onclick='return CowTicket.createUser()'>
              </div>
              <div class='right'>
                <a href='#' onclick='toggleLoginMethod()'>Or login</a>
              </div>
            </div>
          </form>
        </div>
      </div>
      <?php } ?>    
      <div class='clear'></div>
    </div>
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
