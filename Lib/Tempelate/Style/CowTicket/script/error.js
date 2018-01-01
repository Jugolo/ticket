var error = {
  selectAll : function(){
    this.render(function(e){
      e.checked = true;
    });
    this.updateDelete();
  },
  
  unselectAll : function(){
    this.render(function(e){
      e.checked = false;
    });
    this.updateDelete();
  },
  
  updateDelete : function(){
    document.getElementById("delete-button").style.display = this.countChecked() == 0 ? "none" : "inline-block";
  },
  
  countChecked : function(){
    var count = 0;
    this.render(function(e){
      if(e.checked)
        count++;
    });
    return count;
  },
  
  render : function(callback){
    var select = document.getElementsByClassName("error-select");
    for(var i=0;i<select.length;i++){
      callback(select[i]);
    }
  }
};