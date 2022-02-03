const disableElemenets = [
    "button"
    ];

function EM(dom){
  this.dom = dom;
}

EM.prototype.parent = function(){
  var node = this.dom.parentNode;
  if(node === null)
    return null;
  return element(node);
};

EM.prototype.value = function(){
  if(arguments.length > 0){
    if(this.dom instanceof HTMLInputElement || this.dom instanceof HTMLOptionElement){
      this.dom.value = arguments[0];
      return true;
    }
      
    this.dom.innerHTML = arguments[0];
    return true;
  }
    
  if(this.dom instanceof HTMLInputElement || this.dom instanceof HTMLOptionElement)
    return this.dom.value;
  return this.dom.innerHTML;
};

EM.prototype.getDom = function(){
  return this.dom;
};

EM.prototype.on = function(on, callable){
  if(typeof element.options["on.click.cursor"] == "string" && on == "click"){
      this.style("cursor", element.options["on.click.cursor"]);
  }
  const self = this;
  this.dom.addEventListener(on, (event) => callable.call(self, event));
};

EM.prototype.isVisible = function(){
  return this.dom.offsetParent !== null;
};

EM.prototype.attribute = function(...arg){
  if(arg.length == 1 && typeof arg[0] == "object"){
    for(key in arg[0])
      this.attribute(key, arg[0][key]);
    return;
  }
  if(arg.length == 2)
    this.dom.setAttribute(arg[0], arg[1]);
  
  if(arg.length > 0)
    return this.dom.getAttribute(arg[0]);
};

EM.prototype.style = function(name, value){
  const piece = [];
  const p = name.split('-');
  piece.push(p[0]);
  for(var i=1;i<p.length;i++){
    piece.push(p[i][0].toUpperCase()+p[i].substr(1));
  }
    
  this.dom.style[piece.join("")] = value;
};

EM.prototype.addClass = function(c){
  this.dom.classList.add(c);
};

EM.prototype.removeClass = function(c){
  this.dom.classList.remove(c);
};

EM.prototype.classExists = function(n){
  return this.dom.classList.contains(n);  
};

EM.prototype.append = function(dom){
  if(dom instanceof EM)
    this.dom.appendChild(dom.getDom());
  else{
    if(typeof dom === "string")
      dom = document.createTextNode(dom);
    this.dom.appendChild(dom);
  }
};

EM.prototype.disable = function(bool){
  if(disableElemenets.indexOf(this.dom.tagName.toLowerCase()))
    return false;
    
  this.dom.disabled = typeof bool == "boolean" ? bool : true;
};

EM.prototype.dataSet = function(name, value){
  const data = [];
  const p    = name.split("-");
  if(p.length > 0)
    data.push(p[0]);
  
  for(var i=1;i<p.length;i++)
    data.push(p[i][0].toUpperCase()+p[i].substr(1));
  
  this.dom.dataset[data.join("")] = value;
};

EM.prototype.dataGet = function(name){
  //transform normal to js
  const data = [];
  const p    = name.split("-");
  if(p.length > 0)
    data.push(p[0]);
    
  for(var i=1;i<p.length;i++)
    data.push(p[i][0].toUpperCase()+p[i].substr(1));
    
  const v = this.dom.dataset[data.join("")];
  if(!v)
    return null;
  return v;
};

const element = (function(){
  function getDom(n){
    if(typeof n === "string"){
      return document.querySelectorAll(n);
    }else if(n instanceof HTMLElement){
      return [n];
    }else if(n instanceof NodeList){
      return n;
    }
    
    return [];
  }
  
  function element(n){
    var dom = getDom(n);
    return new Proxy(EM, {
      get : function(obj, name){
        if(typeof EM.prototype[name] !== "undefined"){
          return function(...arg){
            var last = null;
            for(var i=0;i<dom.length;i++){
              var e = new EM(dom[i]);
              last = e[name].apply(e, arg);
            }
            return last;
          };
        }
        
        if(name == "render"){
          return function(callback){
            for(var i=0;i<dom.length;i++){
              callback.apply(new EM(dom[i]));
            }
          };
        }
          
        if(name == "length"){
          return dom.length;   
        }
      },
      getPrototypeOf : function(){
        return EM.prototype;
      }
    });
  }
  
  element.create = function(str){
    return element(document.createElement(str));
  };
    
  element.options = {
      "on.click.cursor" : null
  };
  
  return element;
})();
