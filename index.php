<?php
use Lib\Controler\Page\PageControler;
use Lib\Controler\Page\PageInfo;
use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Report;
use Lib\Config;
use Lib\Plugin\Plugin;
use Lib\Ajax;
use Lib\User\Auth;

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
  
  if(defined("user") && group["showError"] == 1)
    Report::error($errstr);
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

function two_container(string $first, string $two, array $options = []) : string{
  $tag = !empty($options["tag"]) ? $option["tag"] : "span";
  $tag2class = !empty($options["tag2class"]) ? " class='".$options["tag2class"]."'" : "";
  return "<div class='two_container'><{$tag}>{$first}</{$tag}><{$tag}{$tag2class}>{$two}</{$tag}></div>";
}

if(!file_exists("config.php")){
  Lib\Setup\Main::controle();
}

include 'config.php';

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

Plugin::init();
Auth::controleAuth();

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
    notfound();
    return;
  }
  $page->body();
}

function hasRight(array $request) : bool{
  foreach($request as $key){
    if(array_key_exists($key, group)){
      return true;
    }
  }
  
  return false;
}

/**
 Return if the users has rights as admin. If the user is not login it will always return false! 
*/
function hasAdminAccess() : bool{
  if(!defined("user")){
    return false;
  }
  
  return hasRight([
    "showError",
    "handleTickets",
    "changeGroup",
    "handleGroup",
    ]);
}

if(Ajax::isAjaxRequest()){
  Ajax::evulate();
}
ob_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet/less" type="text/css" href="style/main.less">
    <title>Ticket system * <?php echo Config::get("system_name"); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <?php 
        Report::toJavascript();
        Report::unset();
        ?> 
        CowTicket.init();
      }
      
      function toggle(identify){
        var el = document.querySelectorAll(identify);
        for(var i=0;i<el.length;i++){
          el[i].style.display = CowDom.isVisible(el[i]) ? "none" : "block";
        }
      }<?php if(!defined("user")){ ?>
      
      function toggleLoginMethod(){
        var login = document.getElementsByClassName("login")[0];
        var create = document.getElementsByClassName("createAccount")[0];
        if(!CowDom.isVisible(login)){
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
            trigger_ajax("create_account", function(a){
              if(a.create){
                toggle('#login_menu');
                toggleLoginMethod();
                username.value = "";
                password.value = "";
                rpassword.value = "";
                email.value = "";
              }
            }, {
              create_username : username.value,
              create_password : password.value,
              repeat_password : rpassword.value,
              email           : email.value,
              createaccount   : "true"
            });
          }
          return false;
        };
      }<?php } ?>
    </script>
  </head>
  <body onload='onload();'>
    <div id="headmenu">
      <div class='hide head' id='menu-state' onclick='CowTicket.toggleMenu();'>
        &#9776;
      </div>
      <?php if(defined("user")){ ?>
      <div class='head'>
        <div class='button' onclick="if(document.getElementsByClassName('notifi_count')[0].innerHTML != 0)toggle('#notify_menu');">
          <div class='notifi_text'>
            Noticaftion
            <div class='notifi_count'>0</div>
          </div>
        </div>
        <div class='menu' id='notify_menu'>
             
        </div>
      </div>
      <?php } ?>
      <div class='title'>
        <?php
        echo "<a href='?view=front' style='color:blue'>".Config::get("system_name")."</a>";
        if(defined("group") && group["changeSystemName"] == 1){
          echo " <a href='?view=front&changeSystemName=true'>(Change system name)</a>";
        }
        ?>
      </div>
      <div class='clear'></div>
    </div>
    <div id='left-menu'>
      <div class="user">
        <?php if(defined("user")){ ?>
        <div class="title"><?php echo user["username"]; ?></div>
        <ul>
          <li><a href="?view=profile">Profile</a></li>
          <li><a href="?view=front&logout=<?php echo session_id(); ?>">Log out</a></li>
        </ul>
        <?php }else{ ?>
        <div class="login">
          <div class="title">Login</div>
          <form method="post" action="#">
            <div>
              <input type="text" name="username" placeholder="User name">
            </div>
            <div>
              <input type="password" name="password" placeholder="Password">
            </div>
            <div>
              <input type="submit" name="login" value="Login now">
            </div>
          </form>
          <div>
            <a href="#" onclick="toggleLoginMethod();">Or create account</a>
          </div>
        </div>
        <div class="createAccount">
          <div class="title">Create account</div>
          <div>
            <input type="username" id="create_username" name="create_username" placeholder="User name">
          </div>
          <div>
            <input type="password" id="create_password" name="create_password" placeholder="Password">  
          </div>
          <div>
            <input type="password" id="repeat_password" name="repeat_password" placeholder="Repeat password">
          </div>
          <div>
            <input type="email" id="email" name="email" placeholder="Email">
          </div>
          <div>
            <input type='submit' name='createaccount' value='Create account' onclick='return CowTicket.createUser()'>
          </div>
          <div>
            <a href="#" onclick="toggleLoginMethod();">Or login</a>
          </div>
        </div>
        <div class="agree">
          Width this action i accept <a href="?view=agree" target="_blank">this</a>
        </div>
        <?php } ?>
      </div>
      <ul id='menu_table'>
        <li><a href='?view=front'>Front</a></li>
        <?php
        if(defined("user")){
          if(Config::get("cat_open") != 0){
            echo "<li><a href='?view=apply'>Apply</a></li>";
          }
          echo "<li><a href='?view=tickets'>Tickets</a></li>";
          if(hasAdminAccess()){
            echo "<li><span class='pointer'>Admin</span>
            <ul class='child'>";
            if(group["showError"] == 1){
              echo "<li><a href='?view=error'>Error</li>";
            }
            if(group["handleTickets"] == 1){
               echo "<li><a href='?view=handleTickets'>Ticket</a></li>";
            }
            if(group["changeGroup"] == 1){
               echo "<li><a href='?view=users'>User</a></li>";
            }
            if(group["handleGroup"] == 1){
               echo "<li><a href='?view=handleGroup'>Group</a></li>";
            }
            echo "</ul>
            </li>";
          }
        }
        ?>
      </ul>
    </div>
    <div id='container'>
      <?php if(defined("logo")){?>
      <div id='logo' style="background-image: url('<?php echo logo; ?>');">
      </div>
      <?php } ?>
      <div id='area'>
        <?php
        getContext();
        ?>
      </div>
      <div id='copy'>
        <a href='http://ticket.cowscript.dk/ticket'>CowScript</a>  2017 - All Rights Reserved
      </div>
    </div>
  </body>
</html>
<?php
ob_end_flush();
