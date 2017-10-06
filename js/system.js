var CowDom = {
  remove : function(dom){
    dom.parentNode.removeChild(dom);
  },
  
  isVisible : function(dom){
    return dom.offsetParent != null;
  }
}

var CowMenuItem = (function(){
  function CowMenuItem(node){
    this.node = node;
  }
  
  CowMenuItem.prototype.link = function(){
    return this.node.getElementsByTagName("a")[0];
  };
  
  CowMenuItem.prototype.name = function(){
    return this.node.className.substr(5);
  };
  
  return CowMenuItem;
})();

var CowMenu = {
  render : function(callback){
    if(typeof callback !== "function"){
      return;
    }
    
    var dom = document.getElementById("menu_table").getElementsByTagName("li");
    for(var i=0;i<dom.length;i++){
      callback(new CowMenuItem(dom[i]));
    }
  }
}

var CowTicket = {
  init : function(){
    this.notify_id = 0;
    this.dom = CowDom;
    this.controleUpdate();
  },
  
  toggleMenu : function(){
    var menu = document.getElementById("left-menu");
    menu.style.display = CowDom.isVisible(menu) ? "none" : "block";
  },
  
  controleUpdate : function(){
    this.ajax("update", {notify_id : this.notify_id}, function(a){
      if(isUser != a.is_user){
        location.reload(); 
      }
      if(a.unread_ticket){
        this.updateUnread(a.unread_ticket);
      }
      if(a.notify){
        this.updateNotify(a.notify);
      }
      var self = this;
      setTimeout(function(){
        self.controleUpdate();
      }, 5000);
    });
  },
  
  updateNotify : function(notify){
    var count = document.getElementsByClassName("notifi_count");
    if(count.length == 0){
      return;
    }
    
    for(var i=0;i<notify.length;i++){
      var id = parseInt(notify[i].id);
      if(this.notify_id < id){
        this.notify_id = id;
      }
      if(notify[i].seen == 0){
        count[0].innerHTML = parseInt(count[0].innerHTML)+1;
      }
      var item = document.createElement("div");
      item.className = "notify_item";
      
      var status = document.createElement("div");
      status.className = "notify_status";
      var round = document.createElement("div");
      round.className = (notify[i].seen == 1 ? "" : "not_")+"seen";
      round.innerHTML = " ";
      status.appendChild(round);
      item.appendChild(status);
      
      var message = document.createElement("div")
      message.className = "notify_message";
      var link = document.createElement("a");
      link.href = notify[i].link;
      link.innerHTML = notify[i].message;
      message.appendChild(link);
      item.appendChild(message);
      var clear = document.createElement("div");
      clear.className = "clear";
      item.appendChild(clear);
      document.getElementById("notify_menu").appendChild(item);
    }
  },
  
  okay : function(msg){
    var dom = document.createElement("div");
    dom.innerHTML = msg;
    
    var container = document.getElementById("okaycontainer");
    if(!container){
      container = document.createElement("div");
      container.className = "msg okay";
      container.id = "okaycontainer";
      document.getElementsByTagName("BODY")[0].appendChild(container);
    }
    
    container.appendChild(dom);
    var timeout = null;
    dom.onclick = function(){
      CowDom.remove(dom);
      if(container.getElementsByTagName("div").length == 0){
        CowDom.remove(container);
      }
      clearTimeout(timeout);
    };
    
    timeout = setTimeout(function(){
      CowDom.remove(dom);
      if(container.getElementsByTagName("div").length == 0){
        CowDom.remove(container);
      }
    }, 5000);
  },
  
  error : function(msg){
    var dom = document.createElement("div");
    dom.innerHTML = msg;
    
    var container = document.getElementById("errorcontainer");
    if(!container){
      container = document.createElement("div");
      container.className = "msg error";
      container.id = "errorcontainer";
      document.getElementsByTagName("BODY")[0].appendChild(container);
    }
    
    container.appendChild(dom);
    var timeout = setTimeout(function(){
      CowDom.remove(dom);
      if(container.getElementsByTagName("div").length == 0){
        CowDom.remove(container);
      }
    }, 5000);
    
    dom.onclick = function(){
      CowDom.remove(dom);
      if(container.getElementsByTagName("div").length == 0){
        CowDom.remove(container);
      }
      clearTimeout(timeout);
    };
  },
  
  updateUnread : function(count){
    var dom = document.getElementById("unreadticket");
    if(count == 0){
      if(dom){
        this.dom.remove(dom);
      }
    }else{
      if(dom){
        if(dom.innerHTML != count){
          dom.innerHTML = count;
        }
      }else{
        dom = document.createElement("span");
        dom.className = "label";
        dom.id = "unreadticket";
        dom.innerHTML = count;
        var tm = document.getElementById("ticketmenu");
        CowMenu.render(function(item){
          if(item.name() == "tickets"){
            item.link().appendChild(dom);
          }
        });   
      }
    }
  },
  
  ajax : function(){
    if(arguments.length == 0){
      return;
    }
    
    var ajax = new XMLHttpRequest();
    var callback;
    if(arguments.length >= 2 && typeof arguments[arguments.length-1] === "function"){
      callback = arguments[arguments.length-1];
    }
    
    var self = this;
    ajax.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        var json = JSON.parse(this.responseText);
        if(typeof json["error"] !== "undefined"){
          for(var i=0;i<json.error.length;i++){
            self.error(json.error[i]);
          }
          delete json["error"];
        }
        if(typeof json["okay"] !== "undefined"){
          for(var i=0;i<json.okay.length;i++){
            self.okay(json.okay[i]);
          }
          delete json["okay"];
        }
        if(typeof callback === "function"){
          callback.apply(self, [json]);
        }
      }
    };
    var type = "GET";
    var post = undefined;
    if(arguments.length >= 2 && typeof arguments[1] !== "function"){
      type = "POST";
      post = [];
      for(var key in arguments[1]){
        post.push(key+"="+encodeURIComponent(arguments[1][key]));
      }
      post = post.join("&");
    }
    ajax.open(type, "?_ajax="+arguments[0], true);
    if(type == "POST"){
      ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    }
    ajax.send(post);
  }
};

var CowUrl = {
  init : function(){
    var url = decodeURIComponent(window.location.search.substring(1)).split("&");
    this.param = {};
    for(var i=0;i<url.length;i++){
      var data = url[i].split("=");
      this.param[data[0]] = data[1];
    }
  },
  
  get : function(name){
    if(typeof this.param[name] === "undefined"){
      return "";
    }
    
    return this.param[name];
  }
};

CowUrl.init();