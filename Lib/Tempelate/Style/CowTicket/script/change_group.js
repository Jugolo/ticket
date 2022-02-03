System.onload.push(function(){
	element("#removefrom").on("change", function(event){
		var dom = this.getDom();
		var index = dom.selectedIndex;
		CowTicket.ajax().request("change_group", function(respons){
			if(respons.success){
				var option = element.create("option");
				option.value(dom.options[index].value);
				option.append(dom.options[index].text);
				element("#addfrom").append(option);
				dom.options[index].remove();
			}
		}, {gid : dom.options[index].value, uid : CowTicket.get("uid"), add : false});
	});
	element("#addfrom").on("change", function(event){
		var dom = this.getDom();
		var index = dom.selectedIndex;
		CowTicket.ajax().request("change_group", function(respons){
			if(respons.success){
				var option = element.create("option");
				option.value(dom.options[index].value);
				option.append(dom.options[index].text);
				element("#removefrom").append(option);
				dom.options[index].remove();
			}
		}, {gid : dom.options[index].value, uid : CowTicket.get("uid"), add : true});
	});
});
