<?php
namespace Lib\Ext\Page\Agree;

use Lib\Controler\Page\PageView as P;

use Lib\Tempelate;

class PageView implements P{
  public function body(Tempelate $tempelate){
    $tempelate->put("rules", ["Information" => "This agreement is created you as user what there is allow and what is not. Also who has the responsibility in diffrence place.",
"When is this agree valid and in use?" => "Thiss agreement is valid all time you visit this site and you agree this every time you log in and also when you create a new account.<br>
This ruls you can read here will maby be change and is this site owner responsibility to informorm you.<br>
But as you can see in the next section is this open source and this rules is not created of this sites owner.",
"Jugolo" => "Jugolo is the software creator but has no responsibility of what there happens here. Jugolo has trying created this to be 100% secure<br>
But all the data is on the owner of this site webhost and therefor can Jugolo not trying to defend data stored when hackers trying to get it<br>
If you found security failed you are welcommen to make a ticket <a href='http://ticket.cowscript.dk/ticket/'>here</a>",
"Data there came from you" => "All the data you send via this tool may not break you (or where this server is) contries law.<br>
It is also not allow to push data to the system to trying to break it. If you will make test get a copy of this software and make test local.",
"Email" => "This site sending email to you when there happen somthing you have show you ware interestet in. this can be new comment on you ticket"]);
    $tempelate->render("agree");
  }
}