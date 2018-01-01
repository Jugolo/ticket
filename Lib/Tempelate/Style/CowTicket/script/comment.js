System.onload.push(function(){
  if(System.screen.width() <= 768){
    return;
  }
  var body = document.getElementsByClassName("comment-body");
  for(var i=0;i<body.length;i++)
    fixCommentBody(body[i]);
});

function fixCommentBody(body){
  var info = body.getElementsByClassName("comment-information")[0];
  var msg  = body.getElementsByClassName("comment-message")[0];
  
  if(info.offsetHeight > msg.offsetHeight){
    info.style.borderRight = "1px solid black";
  }else{
    msg.style.borderLeft = "1px solid black";
  }
}