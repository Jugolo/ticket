var System = {
  onload      : [],
  unreadCount : 0,
  
  toogleMenu : function(){
    this.dom.toogle(document.getElementById("root-left"));
  },
  
  listenUpdate : function(){
    CowTicket.ajax().request("update", function(data){
      System.updateUnread(data.unread_ticket);
      System.notify.update(data.notify);
    }, {notify_id : this.notify.id});
    setTimeout(function(){
      System.listenUpdate();
    }, 10000);
  },
  
  updateUnread : function(count){
    if(this.unreadCount != count){
      if(this.unreadCount < count){
        this.report.okay("You have "+(count-this.unreadCount)+" unseen tickets");
      }
      this.unreadCount = count;
    }
  },
  
  isMobile : function(){
    return this.screen.width() <= 768;
  }
};

System.onclick = {
  buffer : [],
  
  add : function(callback){
    if(typeof callback == "function")
      this.buffer.push(callback);
  },
  
  remove : function(callback){
    this.buffer.splice(this.buffer.indexOf(callback, 1));
  },
  
  trigger : function(e){
    var obj = {
      isIn : function(dom){
        var target = e.target;
        while(target){
          if(target == dom)
            return true;
          target = target.parentNode;
        }
        return false;
      }
    };
    for(var i=0;i<this.buffer.length;i++)
      this.buffer[i](obj);
  },
  
  getIndex : function(callback){
    for(var i=0;i<this.buffer.length;i++){
      if(this.buffer[i] == callback)
        return i;
    }
    return -1;
  }
};

System.dom = {
  remove : function(dom){
    dom.parentNode.removeChild(dom);
  },
  
  isVisible : function(dom){
    return dom.offsetParent != null;
  },
  
  toogle : function(dom){
    dom.style.display = this.isVisible(dom) ? "none" : "block";
  },
  
  appendBegin : function(dom, node){
    if(dom.firstChild)
      dom.insertBefore(node, dom.firstChild);
    else
      dom.appendChild(node);
  },
};

System.screen = {
  width : function(){
    return window.innerWidth;
  }
};

System.report = {
  error : function(msg){
    var container = document.getElementById("root-error");
    if(!container){
      container = document.createElement("div");
      container.setAttribute("id", "root-error");
      System.dom.appendBegin(document.getElementById("root"), container);
    }
    var div = document.createElement("div");
    div.innerHTML = msg;
    container.appendChild(div);
    setTimeout(function(){
      System.dom.remove(div);
      if(container.getElementsByTagName("div").length == 0){
        System.dom.remove(container);
      }
    }, 5000);
  },
  
  okay : function(msg){
    var container = document.getElementById("root-okay");
    if(!container){
      container = document.createElement("div");
      container.setAttribute("id", "root-okay");
      System.dom.appendBegin(document.getElementById("root"), container);
    }
    
    var div = document.createElement("div");
    div.innerHTML = msg;
    container.appendChild(div);
    setTimeout(function(){
      System.dom.remove(div);
      if(container.getElementsByTagName("div").length == 0){
        System.dom.remove(container);
      }
    }, 5000);
  }
};

System.notify = {
  id     : 0,
  unseen : 0,
  cache  : [],
  
  update : function(data){
    if(data.length == 0)
      return;
    this.cache = this.cache.concat(data);
    for(var i=0;i<data.length;i++){
      if(data[i].seen == 0)
        this.unseen++;
      var id = parseInt(data[i].id);
      if(this.id < id)
        this.id = id;
    }
    
    this.getUnseen(this.unseen);
  },
  
  getUnseen : function(num){
    var dom = document.getElementsByClassName("notify")[0].getElementsByClassName("count")[0];
    if(typeof num === "number")
      dom.innerHTML = num;
   
    return dom.innerHTML;
  },
  
  showList : function(dom){
    if(this.cache.length == "0")
      return;
    
    var menu = document.getElementById("notify_menu");
    if(!menu){
      menu = document.createElement("div");
      menu.id = "notify_menu";
      document.body.appendChild(menu);
    }else{
      System.dom.remove(menu);
      return;
    }
    
    var title = document.createElement("div");
    title.className = "title mobile";
    title.innerHTML = "Notify";
    menu.appendChild(title);
    
    for(var i=0;i<this.cache.length;i++){
      var div = document.createElement("div");
      div.className = "item";
      var status = document.createElement("div");
      status.className = "status";
      var dot = document.createElement("div");
      if(this.cache[i].seen == 0){
        dot.className = "notseen";  
      }
      dot.innerHTML = " ";
      
      var message = document.createElement("div");
      message.className = "message";
      
      var a = document.createElement("a");
      a.href= this.cache[i].link;
      a.innerHTML = this.cache[i].message;
      message.appendChild(a);
      
      status.appendChild(dot);
      div.appendChild(status);
      
      div.appendChild(message);
      
      var clear = document.createElement("div");
      clear.className = "clear";
      div.appendChild(clear);
      
      menu.appendChild(div);
    }
    
    if(!System.isMobile()){
      menu.style.top = (parseInt(dom.offsetTop)+20)+"px";
      menu.style.left = dom.offsetLeft+"px";
    }
  }
};

window.onerror = function(msg){
  alert(msg);
};

System.onload.push(function(){
  var profile = element("#profile-container");
  var button = element("#root-head-right .profile");
  if(profile.length){
    button.on("click", function(){
      profile.style("display", "block");
      System.onclick.add(function g(h){
        if(!h.isIn(profile.getDom()) && !h.isIn(button.getDom())){
          System.onclick.remove(g);
          profile.style("display", "none");
        }
      });
    });
    var db = button.getDom();
    profile.style("top", (db.offsetTop + db.offsetHeight + 10)+"px");
    var cw = document.body.clientWidth;
    var left = db.offsetLeft;
    if(left + db.offsetWidth > cw)
      profile.style("right", "3px");
    else
      profile.style("left", left+"px");
  }
});

document.onclick = function(e){
  System.onclick.trigger(e);
};

window.onload = function(){
  var ajax = CowTicket.ajax();
  ajax.onError(function(msg){
    System.report.error(msg);
  });
  ajax.onOkay(function(msg){
    System.report.okay(msg);
  });
  System.listenUpdate();
  for(var i=0;i<System.onload.length;i++){
    System.onload[i]();
  }
  
  element("#root-head-left .lang-menu .head").on("click", function(){
	 var elm = element("#root-head-left .lang-menu .list");
	 if(elm.isVisible()){
		 elm.style("display", "none");
	 }else{
		 elm.style("display", "block");
	 }
	 var self = this;
	 System.onclick.add(function g(h){
        if(!h.isIn(elm.getDom()) && !h.isIn(self.getDom())){
          System.onclick.remove(g);
          elm.style("display", "none");
        }
      });
  });
  
  element("#root-head-left .lang-menu .list .lang-item").on("click", function(){
	  window.location.href = "?view=front&lang="+this.dataGet("langcode");
  });
};
