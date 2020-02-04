System.onload.push(function(){
  element(".ticket").on("click", function(){
    location.href = "?view=tickets&ticket_id="+this.attribute("ticket-id");
  });
  
  element(".pages button").on("click", function(){
    const url = this.parent().dataGet("url");
    if(this.dataGet("page"))
      location.href = url+"&page="+this.dataGet("page");
    else{
      var current = parseInt(this.parent().dataGet("current"));
      switch(this.dataGet("state")){
        case "front":
          location.href = url+"&page=0";
          break;
        case "back":
          location.href = url+"&page="+(current - 1);
          break;
        case "next":
          location.href = url+"&page="+(current + 1);
          break;
        case "last":
          location.href = url+"&page="+(this.parent().dataGet("last") - 1);
          break;
      }
    }
  });
});