<?php
namespace Lib\Setup;

use Lib\User\Auth;
use Lib\Database;
use Lib\Ext\Notification\Notification;
use Lib\Log;

class Install{
  private static $okay = false;
  
  public static function install(){
    if(empty($_COOKIE["accept_cookie"]))
      session_start();
    
    switch(empty($_GET["step"]) ? "1" : $_GET["step"]){
      case "1":
        self::information();
      break;
      case "2":
        self::generateConfig();
      break;
      case "3":
        self::createTable();
      break;
      case "4":
        self::createData();
      break;
      case "5":
        self::makeConfig();
      break;
      default:
        echo "Unknown step";
    }
    exit;
  }
  
  private static function makeConfig(){
    if(empty($_SESSION["setup"])){
      header("location: ?step=2");
      exit;
    }
    $fopen = fopen("config.php", "w+");
    fwrite($fopen, "<?php");
    fwrite($fopen, "\r\ndefine('DB_HOST', '{$_SESSION["setup"]["db_host"]}');");
    fwrite($fopen, "\r\ndefine('DB_USER', '{$_SESSION["setup"]["db_user"]}');");
    fwrite($fopen, "\r\ndefine('DB_PASS', '{$_SESSION["setup"]["db_password"]}');");
    fwrite($fopen, "\r\ndefine('DB_TABLE', '{$_SESSION["setup"]["db_table"]}');");
    fwrite($fopen, "\r\ndefine('DB_PREFIX', '{$_SESSION["setup"]["db_prefix"]}');");
    fwrite($fopen, "\r\ndefine('db_driver', 'Mysqli');");
    fclose($fopen);
    echo "<h3 style='color:green;'>Setup done</h3>";
    echo "The setup is done and you can refreace the page and take you new system in use<br>
    Thanks for useing this tool. if you have quistion please feel free to use it <a href='http://ticket.cowscript.dk'>HERE</a>";
  }
  
  private static function createData(){
    self::$okay = true;
    if(empty($_SESSION["setup"])){
      header("location: ?step=2");
      exit;
    }
    if(!empty($_POST["create"])){
      self::createSqlData();
    }
    echo "<h3 style='color:green;text-align:center;'>Create user and data</h3>
    <br>".(self::$okay ? "" : "<h3 style='color:red;text-align:center'>Somthing went wrong try again</h3>")."
    Now it is time to create you admin account!
    <form method='post' action='#'>
    <table>
      <tr>
        <th>Username</th>
        <td><input type='text' name='username'></td>
      </tr>
      <tr>
        <th>Password</th>
        <td><input type='password' name='password'></td>
      </tr>
      <tr>
        <th>Repeat password</th>
        <td><input type='password' name='repeat_password'></td>
      </tr>
      <tr>
        <th>Email</th>
        <td><input type='email' name='email'><td>
      </tr>
    </table>
    <hr>
    <h3 style='color:green;text-align:center;'>Default data in the system</h3>
    <table>
      <tr>
        <th>System name</th>
        <td><input type='text' name='system_name'></td>
      </tr>
      <tr>
        <td colspan='2'>
          <input type='submit' name='create' value='Create data' style='width:100%'>
        </td>
      </tr>
    </table>
    </form>";
  }
  
  private static function createSqlData(){
     if(empty($_POST["username"]) || !trim($_POST["username"])){
       self::$okay = false;
       return;
     }
    
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      self::$okay = false;
      return;
    }elseif(empty($_POST["repeat_password"]) || !trim($_POST["repeat_password"])){
      self::$okay = false;
      return;
    }elseif($_POST["password"] != $_POST["repeat_password"]){
      self::$okay = false;
      return;
    }else if(empty($_POST["system_name"]) || !trim($_POST["system_name"])){
      self::$okay = false;
      return;
    }
    
    if(empty($_POST["email"]) || !trim($_POST["email"])){
      self::$okay = false;
      return;
    }
    
    define("DB_PREFIX", $_SESSION["setup"]["db_prefix"]);
    $db = Database::get();
    
    $db->query("INSERT INTO `".DB_PREFIX."group` VALUES (1, 'User'),
                                           (2, 'Admin');");
    $db->query("INSERT INTO `".DB_PREFIX."access` VALUES (2, 'CATEGORY_CREATE'),
                                            (2, 'CATEGORY_DELETE'),
                                            (2, 'CATEGORY_CLOSE'),
                                            (2, 'CATEGORY_APPEND'),
                                            (2, 'CATEGORY_ITEM_DELETE'),
                                            (2, 'CATEGORY_SETTING'),
                                            (2, 'CATEGORY_SORT'),
                                            (2, 'USER_GROUP'),
                                            (2, 'USER_PROFILE'),
                                            (2, 'USER_DELETE'),
                                            (2, 'USER_LOG'),
                                            (2, 'USER_ACTIVATE'),
                                            (2, 'GROUP_CREATE'),
                                            (2, 'GROUP_DELETE'),
                                            (2, 'GROUP_ACCESS'),
                                            (2, 'GROUP_STANDART'),
                                            (2, 'ERROR_SHOW'),
                                            (2, 'ERROR_DELETE'),
                                            (2, 'SYSTEM_FRONT'),
                                            (2, 'SYSTEM_NAME'),
                                            (2, 'SYSTEMLOG_SHOW'),
                                            (2, 'TEMPELATE_SELECT'),
                                            (2, 'PLUGIN_INSTALL'),
                                            (2, 'PLUGIN_UNINSTALL'),
                                            (2, 'CATEGORY_ACCESS');");
    $db->query("INSERT INTO `".DB_PREFIX."config` VALUES ('version', '".Main::SETUP_VERSION."'),
                                            ('standart_group', '1'),
                                            ('cat_open', '0'),
                                            ('front', ''),
                                            ('system_name', '{$db->escape($_POST["system_name"])}'),
                                            ('tempelate', 'CowTicket'),
                                            ('standart_language', 'En');");
    $db->query("INSERT INTO `".DB_PREFIX."cronwork` (`id`, `cronwork`, `next`, `interval`) VALUES (NULL, 'Lib\\\\Cronwork\\\\Image::gc', 0, 2052000)");
    $db->query("INSERT INTO `".DB_PREFIX."cronwork` (`id`, `cronwork`, `next`, `interval`) VALUES (NULL, 'Lib\\\\Ticket\\\\TicketDeleter::gc', '0', '2052000');");
    $db->query("INSERT INTO `".DB_PREFIX."file_group` (`id`, `name`) VALUES (NULL, '@language.IMAGE');");
    $db->query("INSERT INTO `".DB_PREFIX."file_extension` (`gid`, `name`, `mimetype`) VALUES
                                                            ('1', 'jpg', 'image/jpg'),
                                                            ('1', 'jpeg', 'image/jpg'),
                                                            ('1', 'png', 'image/png');");
    
    define("force_lang", "En");
    $id =Auth::createUser(
      $_POST["username"],
      $_POST["password"],
      $_POST["email"],
      true
      );
    $db->query("INSERT INTO `".DB_PREFIX."grup_member` (`gid`, `uid`) VALUES ('2', '{$id}');");
    if(!is_dir("Lib/Temp"))
      mkdir("Lib/Temp");
    Log::system("LOG_SYSTEM_INSTALL");
    header("location: ?step=5");
    exit;
  }
  
  private static function createTable(){
    if(empty($_SESSION["setup"])){
      header("location: ?step=2");
      exit;
    }
    
    $prefix = $_SESSION["setup"]["db_prefix"];
    
    $table = [
        "plugin"    => "CREATE TABLE `{$prefix}plugin` ( 
                          `id` INT(11) NOT NULL AUTO_INCREMENT,
                          `path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                          PRIMARY KEY(`id`)
                        ) ENGINE = InnoDB DEFAULT CHARSET=utf8;",
        "access"    => "CREATE TABLE IF NOT EXISTS `{$prefix}access` (
                          `gid` int(11) NOT NULL,
                          `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "catogory" => "CREATE TABLE IF NOT EXISTS `{$prefix}catogory` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `open` int(1) NOT NULL,
                         `age` int(11) DEFAULT NULL,
                         `input_count` int(11) DEFAULT NULL,
                         `ticket_count` int(11) DEFAULT NULL,
                         `sort_ordre` int(11) DEFAULT NULL,
                         PRIMARY KEY (`id`)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "category_item"  => "CREATE TABLE IF NOT EXISTS `{$prefix}category_item` (
                               `id` int(11) NOT NULL AUTO_INCREMENT,
                               `cid` int(11) NOT NULL,
                               `type` int(11) NOT NULL,
                               `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                               `placeholder` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                               PRIMARY KEY (`id`)
                             ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "comment"  => "CREATE TABLE IF NOT EXISTS `{$prefix}comment` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `cid` int(11) NOT NULL,
                         `tid` int(11) NOT NULL,
                         `uid` int(11) NOT NULL,
                         `public` int(1) NOT NULL,
                         `created` int(11) NOT NULL,
                         `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
                         `parsed_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
                         PRIMARY KEY (`id`)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "config"   => "CREATE TABLE IF NOT EXISTS `{$prefix}config` (
                         `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `value` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "error"    => "CREATE TABLE IF NOT EXISTS `{$prefix}error` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `errno` int(11) NOT NULL,
                         `errstr` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `errfile` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `errline` int(11) NOT NULL,
                         `errtime` datetime NOT NULL,
                         PRIMARY KEY (`id`)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "group"    => "CREATE TABLE IF NOT EXISTS `{$prefix}group` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         PRIMARY KEY (`id`)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "log"      => "CREATE TABLE IF NOT EXISTS `{$prefix}log` ( 
                         `id` INT(11) NOT NULL AUTO_INCREMENT ,
                         `type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,
                         `created` int(11) NOT NULL ,
                         `uid` INT(11) NOT NULL,
                         `tid` INT(11) NOT NULL,
                         `message` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, 
                         `arg` TEXT CHARACTER SET utf8 COLLATE utf8_bin NULL, 
                         PRIMARY KEY (`id`)
                       ) ENGINE = InnoDB DEFAULT CHARSET=utf8;",
        "notify"   => "CREATE TABLE IF NOT EXISTS `{$prefix}notify` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `uid` int(11) NOT NULL,
                         `item_id` int(11) NOT NULL,
                         `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `message` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `arg` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                         `created` int(11) NOT NULL,
                         `seen` int(1) NOT NULL,
                         PRIMARY KEY (`id`)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "notify_setting" => "CREATE TABLE IF NOT EXISTS `{$prefix}notify_setting` (
                              `uid` int(11) NOT NULL,
                              `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "ticket"  => "CREATE TABLE IF NOT EXISTS `{$prefix}ticket` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `cid` int(11) NOT NULL,
                        `uid` int(11) NOT NULL,
                        `comments` int(11) NOT NULL,
                        `admin_comments` int(11) NOT NULL,
                        `created` int(11) NOT NULL,
                        `user_changed` int(11) NOT NULL,
                        `admin_changed` int(11) NOT NULL,
                        `open` int(1) NOT NULL,
                        PRIMARY KEY (`id`)
                      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
         "ticket_track" => "CREATE TABLE IF NOT EXISTS `{$prefix}ticket_track` (
                              `uid` int(11) NOT NULL,
                              `cid` int(11) NOT NULL,
                              `tid` int(11) NOT NULL,
                              `visit` int(11) NOT NULL
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
         "ticket_value" => "CREATE TABLE IF NOT EXISTS `{$prefix}ticket_value` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `cid` int(11) NOT NULL,
                              `hid` int(11) NOT NULL,
                              `text` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                              `type` int(11) NOT NULL,
                              `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "user" => "CREATE TABLE IF NOT EXISTS `{$prefix}user` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                     `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                     `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                     `salt` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                     `isActivatet` int(1) NOT NULL,
                     `birth_day` int(11) DEFAULT NULL,
                     `birth_month` int(11) DEFAULT NULL,
                     `birth_year` int(11) DEFAULT NULL,
                     `lang` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                     PRIMARY KEY (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        "cronwork" => "CREATE TABLE `{$prefix}cronwork` (
                         `id` INT(11) NOT NULL AUTO_INCREMENT,
                         `cronwork` VARCHAR(255) NOT NULL,
                         `next` INT(11) NOT NULL,
                         `interval` INT(11) NOT NULL,
                         PRIMARY KEY (`id`)
                       ) ENGINE = InnoDB;",
        "file_group" => "CREATE TABLE `{$prefix}file_group`(
                           `id` INT(11) NOT NULL AUTO_INCREMENT,
                           `name` VARCHAR(255) NOT NULL,
                           PRIMARY KEY  (`id`)
                         ) ENGINE = InnoDB;",
        "file_extension" => "CREATE TABLE `{$prefix}file_extension`(
                               `id` INT(11) NOT NULL AUTO_INCREMENT, 
                               `gid` INT(11) NOT NULL,  
                               `name` VARCHAR(255) NOT NULL , 
                               `mimetype` VARCHAR(255) NOT NULL,   
                               PRIMARY KEY  (`id`)
                             ) ENGINE = InnoDB;",
        "flood" => "CREATE TABLE `{$prefix}flood`(
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `time` int(11) NOT NULL,
                      `type` varchar(255) COLLATE utf8mb4_bin NOT NULL,
                      `ip` varchar(255) COLLATE utf8mb4_bin NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;",
        "files" => "CREATE TABLE `{$prefix}files`(
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `item_id` int(11) NOT NULL,
                      `name` varchar(255) COLLATE utf8mb4_bin NOT NULL,
                      `created` int(11) NOT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;",
         "group_member" => "CREATE TABLE IF NOT EXISTS `{$prefix}grup_member` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `gid` int(11) NOT NULL,
                              `uid` int(11) NOT NULL,
                              PRIMARY KEY (`id`)
                           ) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;",
         "category_access" => "CREATE TABLE IF NOT EXISTS `{$prefix}category_access` (
                                 `gid` int(11) NOT NULL,
                                 `cid` int(11) NOT NULL,
                                 `name` varchar(255) NOT NULL
                           ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
      ];
    $db = Database::get();
    
    echo "<h3 style='color:green;text-align:center;'>Create tables</h3><br><br>";
    self::$okay = true;
    foreach($table as $name => $sql){
      if($db->query($sql)){
        echo "<span style='color:green'>Created '{$name}'</span><br>";
      }else{
        self::$okay = false;
        echo "<span style='color:red;'>Failed to create '{$name}'</span><br>";
        break;
      }
    }
    if(self::$okay){
      echo "<a href='?step=4'>Go to next step</a>";
    }
  }
  
  private static function generateConfig(){
    self::$okay = true;
    if(!empty($_POST["test"])){
      self::testConfig();
    }
    echo "<h3 style='color:green;text-align:center;'>Generate config</h3>
    ".(self::$okay ? "" : "<br><br><span style='color:red'>The data was wrong</span><br><br>")."
    <form method='post' action='#'>
      <table style='border-collapse:collapse;'>
        <tr>
          <th>Database host</th>
          <td><input type='text' name='host'></td>
        </tr>
        <tr>
          <th>Database user</th>
          <td><input type='text' name='user'></td>
        </tr>
        <tr>
          <th>Database password</th>
          <td><input type='text' name='password'></td>
        </tr>
        <tr>
          <th>Database table</th>
          <td><input type='text' name='table'></td>
        </tr>
        <tr>
          <th>Database prefix</th>
          <td><input type='text' name='prefix'></td>
        </tr>
        <tr>
          <td colspan='2'>
            <input type='submit' name='test' value='Test config' style='width:100%;'>
          </td>
        </tr>
      </table>
    </form>";
  }
  
  private static function testConfig(){
    //wee test database here
    if(empty($_POST["host"]) || !trim($_POST["host"])){
      self::$okay = false;
      return;
    }
    if(empty($_POST["user"]) || !trim($_POST["user"])){
      self::$okay = false;
      return;
    }
    if(empty($_POST["password"]) || !trim($_POST["password"])){
      self::$okay = false;
      return;
    }
    if(empty($_POST["table"]) || !trim($_POST["table"])){
      self::$okay = false;
      return;
    }
    if(empty($_POST["prefix"]) || !trim($_POST["prefix"])){
      self::$okay = false;
      return;
    }
    $mysqli = new \Mysqli($_POST["host"], $_POST["user"], $_POST["password"], $_POST["table"]);
    if($mysqli->connect_error){
      self::$okay = false;
      return;
    }
    $_SESSION["setup"] = [
        "db_host"     => $_POST["host"],
        "db_user"     => $_POST["user"],
        "db_password" => $_POST["password"],
        "db_table"    => $_POST["table"],
        "db_prefix"   => $_POST["prefix"],
        "db_driver"   => "Mysqli"
      ];
    header("location: ?step=3");
    exit;
  }
  
  private static function information(){
    echo "<h3 style='text-align:center;color:green;'>Welcommen to the <strong>CowTicket</strong> setup script</h3>
    <br>
    <br>
    This simple setup script will take you to diffrence page so you can use the ticket system<br>
    The first step is get database information so wee can put data in it.<br>
    Next wee will insert table<br>
    Create acount for you and insert the last data.<br>
    And the last step is create config file and then are you done<br>
    <br>
    But let us se if all is at it should!<br>
    <br>
    ".self::controle()."
    <br>
    Thanks to use this tool <strong>CowTicket`s team</strong>
    <br>
    ".(self::$okay ? "<a href='?step=2'>Go to step 2</a>" : "<strong>Test failed. please fix them before you go to next step</strong>");
  }
  
  private static function controle(){
    $text = "";
    
    if(version_compare(phpversion(), '7.0.0', '<')){
      return "<span style='color:red;'>You dont use php version 7.0 or heigher</span><br>";
    }else{
      $text .= "<span style='color:green'>You use php 7.0 or heigher</span><br>";
    }
    
    if(!extension_loaded("mysql") && !class_exists("mysqli")){
      return $text."<span style='color:red'>You has not install the mysqli extension</span><br>";
    }else{
      $text .= "<span style='color:green'>You has installed the mysqli extension</span><br>";
    }
    
    self::$okay = true;
    return $text;
  }
}
