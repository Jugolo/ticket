System.onload.push(function(){
  var menu = document.getElementsByClassName("access-menu")[0];
  if(System.dom.isVisible(menu)){
    setMenuSize(menu.clientWidth, menu.getElementsByTagName("span"));
  }
});

function setMenuSize(container_width, doms){
  var width = Math.round(container_width / doms.length);
  for(var i=0;i<doms.length;i++){
    doms[i].style.width = (width-1)+"px";
    menuClick(doms[i]);
  }
  show(doms[0].innerHTML);
}

function menuClick(dom){
  dom.onclick = function(){
    show(dom.innerHTML);
  };
}

function show(name){
  var dom = document.getElementsByClassName("access-data");
  for(var i=0;i<dom.length;i++){
    if(dom[i].getAttribute("for") == name)
      dom[i].style.display = "block";
    else
      dom[i].style.display = "none";
  }
}