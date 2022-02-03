var CowTicket = new ((function(){
  function escapePost(obj){
    var data = [];
    for(var key in obj)
      data.push(encodeURIComponent(key)+"="+encodeURIComponent(obj[key]));
    return data.join("&");
  }
  
  function handleReport(obj, report){
    if(typeof report.OKAY !== "undefined" && typeof obj.okay !== "undefined"){
      var okay = report.OKAY;
      for(var i=0;i<okay.length;i++)
        obj.okay(okay[i]);
    }
    
    if(typeof report.ERROR !== "undefined" && typeof obj.error !== "undefined"){
      var error = report.ERROR;
      for(var i=0;i<error.length;i++)
        obj.error(error[i]);
    }
  }
  
  var ajax = {
    request : function(uri, callback, posts){
      var request = new XMLHttpRequest();
      var self = this;
      request.onreadystatechange = function(){
        if(this.readyState === 4 && this.status === 200){
          try{
            var json = JSON.parse(this.responseText);
            handleReport(self, json.reports);
            callback(json.data);
          }catch(e){
            alert(e);
          }
        }
      };
      request.open(typeof posts === "undefined" ? "GET" : "POST", "?_ajax="+uri, true);
      if(typeof posts !== "undefined"){
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      }
      request.send(typeof posts === "undefined" ? undefined : escapePost(posts));
    },
    
    onError : function(callback){
      this.error = callback; 
    },
    
    onOkay : function(callback){
      this.okay = callback;
    }
  };
  
  var queryParam = {}
  
  function CowTicket(){
    var query_part = window.location.search.substr(1).split('&');
	for(var i=0;i<query_part.length;i++){
		var [key, value] = query_part[i].split("=");
		queryParam[decodeURIComponent(key)] = decodeURIComponent(value);
	}
  }
  
  CowTicket.prototype.ajax = function(){
    return ajax;
  };
  
  CowTicket.prototype.get = function(key){
	  if(typeof queryParam[key] == "undefined")
		  return "";
	  return queryParam[key];
  };
  
  return CowTicket;
})())();