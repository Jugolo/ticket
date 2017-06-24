<?php
use Lib\Controler\Page\PageControler;
use Lib\Controler\Page\PageInfo;
use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Error;
use Lib\Okay;

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
      Error::report($errstr);
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

include "ajax.php";

function two_container(string $first, string $two, array $options = []) : string{
  $tag = !empty($options["tag"]) ? $option["tag"] : "span";
  $tag2class = !empty($options["tag2class"]) ? " class='".$options["tag2class"]."'" : "";
  return "<div class='two_container'><{$tag}>{$first}</{$tag}><{$tag}{$tag2class}>{$two}</{$tag}></div>";
}

if(!file_exists("config.php")){
  Lib\Setup\Main::controle();
}

include 'config.php';

function doLogin(){
  $error = Error::count();
  if(empty($_POST["username"]) || !trim($_POST["username"])){
    Error::report("Missing username");
  }
  
  if(empty($_POST["password"]) || !trim($_POST["password"])){
    Error::report("Missing password");
  }
  
  if(Error::count() == $error){
    $db = Database::get();
    $row = $db->query("SELECT `id`, `password`, `salt`, `isActivatet` FROM `user` WHERE LOWER(`username`)='".$db->escape(strtolower($_POST["username"]))."'")->fetch();
    if($row){
      if(Lib\User\Auth::salt_password($_POST["password"], $row->salt) == $row->password){
        if($row->isActivatet == 1){
          $_SESSION["uid"] = $row->id;
          Okay::report("You are now login");
        }else{
          Error::report("You account is not activatet yet!");
        }
      }else{
        Error::report("Faild to find username or/and password");
      }
    }else{
      Error::report("Failed to find username or/and password");
    }
  }
  header("location: #");
  exit;
}

function doCreate(){
  $error = Error::count();
  if(empty($_POST["create_username"]) || !trim($_POST["create_username"])){
    Error::report("Missing username");
  }
  
  $p = $r = true;
  
  if(empty($_POST["create_password"]) || !trim($_POST["create_password"])){
    Error::report("Missing password");
    $p = false;
  }
  
  if(empty($_POST["repeat_password"]) || !trim($_POST["repeat_password"])){
    Error::report("Missing repeat password");
    $r = false;
  }
  
  if($p && $r && $_POST["repeat_password"] != $_POST["create_password"]){
    Error::report("The two password is not the same");
  }
  
  if(empty($_POST["email"]) || !trim($_POST["email"])){
    Error::report("Missing email");
  }
  
  if($error == Error::count()){
    if(Lib\User\Auth::controleDetail($_POST["create_username"], $_POST["email"]) == null){
      Lib\User\Auth::createUser($_POST["create_username"], $_POST["create_password"], $_POST["email"], false);
      Okay::report("You account is created. Please look in you email for activate it");
      if(is_ajax()){
        ajax_var("create", true);
      }
    }else{
      if(is_ajax()){
        ajax_var("create", false);
      }
      Error::report("The username or/and email is taken.");
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
    Error::report("Could not find the account. Maby it is already activated?");
  }else{
    $db->query("UPDATE `user` SET `isActivatet`=1 WHERE `id`=".$info->id);
    Okay::report("The account is now activated and you can now login");
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
  return "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
}

function getStandartGroup(){
  return Lib\Config::get("standart_group");
}

function getContext(){
  $page = PageControler::getPage();
  if(!$page){
    return;
  }
  $page->body();
}

function getMenu(){
  echo "<div id='menu'".(defined("logo") ? "" : " class='nologo'").">";
  $table = new Lib\Html\Table();
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
      var isUser = <?php echo defined("user") ? "true" : "false"; ?>;
      function onload(){
        <?php Error::toJavascript(); ?> 
        <?php Okay::toJavascript(); ?> 
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
          </form>
          <form method='post' action='#'>
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
        <a href='http://ticket.cowscript.dk/'>CowScript</a>  2017 - All Rights Reserved
      </div>
    </div>
  </body>
</html>
<?php
ob_end_flush();
