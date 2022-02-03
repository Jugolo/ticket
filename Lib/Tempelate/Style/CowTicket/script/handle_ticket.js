var HandleTicket = {
  createTypeChange : function(value){
    var e = element("#input-placeholder");
    element("#input-placeholder .item").style("display", "none");
    switch(value){
      case "3":
        element("#input-placeholder .select").style("display", "block");
        break;
      case "4":
        element("#input-placeholder .file").style("display", "block");
        break;
      default:
        element("#input-placeholder .stand").style("display", "block");
        break;
    }
    
    element("#placeholder-input .item").style("display", "none");
    if(value == 4)
      element("#placeholder-input .file").style("display", "block");
    else
      element("#placeholder-input .stand").style("display", "block");
    //document.getElementById("input-placeholder").innerHTML = value == 3 ? "Options. Seperate by comma" : "Placeholder";
  }
};