var CowDom = {
  remove : function(dom){
    dom.parentNode.removeChild(dom);
  }
}

var CowTicket = {
  init : function(){
    this.dom = CowDom;
    this.controleUpdate();
  },
  
  controleUpdate : function(){
    this.ajax("update", function(a){
      this.updateUnread(a.unread_ticket);
      var self = this;
      setTimeout(function(){
        self.controleUpdate();
      }, 5000);
    });
  },
  
  error : function(msg){
    var dom = document.createElement("div");
    dom.innerHTML = msg;
    
    var container = document.getElementById("errorcontainer");
    if(!container){
      container = document.createElement("div");
      container.className = "msg error";
      document.getElementsByTagName("BODY")[0].appendChild(container);
    }
    
    container.appendChild(dom);
    setTimeout(function(){
      CowDom.remove(dom);
      if(container.getElementsByTagName("div").length == 0){
        CowDom.remove(container);
      }
    }, 5000);
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
        if(tm){
          tm.appendChild(dom);
        }
      }
    }
  },
  
  ajax : function(){
    if(arguments.length == 0){
      return;
    }
    
    var ajax = new XMLHttpRequest();
    var callback;
    if(arguments.length >= 2 && typeof arguments[1] === "function"){
      callback = arguments[1];
    }else{
      return;
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
        callback.apply(self, [json]);
      }
    };
    ajax.open("GET", "?_ajax="+arguments[0], true);
    ajax.send();
  }
};
