/** Version 1.1 **/

var div_information = null;
var div_body = null;
var wait = null;
var ready = null;
var error_page = "404: Page not found<br>";

//save_history
var global_prog, global_param, global_out, global_method;

//for stop double click
var is_action = false;

function initializeKAJAX(body, info, lwait, lready) {
	div_body = body;
	div_information = info;
	wait = lwait;
	ready = lready;
};

function loadXMLDoc(prog, param, out, method) {
	
	var xmlhttp = null;
	var info = null;
	var info_wait = null;
	var name_prog = getProg(prog);
	
	if (div_information != null) {
		info = document.getElementById(div_information);
		info_wait = document.createElement("SPAN");
	    info_wait.setAttribute("id",prog);
	    info_wait.innerHTML = wait + "Attesa per " + name_prog + "<br>";
	    
	    info.appendChild(info_wait);
	}
	
	if (window.XMLHttpRequest) {
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp = new XMLHttpRequest();
	} else {
		// code for IE6, IE5
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				if (info != null) {
					info_wait.innerHTML = ready + " " + name_prog + " is ready! <br>";
				}
			} else {
				is_action = false;
				
				if (info != null) {
					info_wait.innerHTML = error_page;
				}
			}
			
			if (info != null) {
				setTimeout(function(){delete_wait(info, info_wait);}, 3000);
			}
		}
		
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			document.getElementById(out).innerHTML = xmlhttp.responseText;
			evalJS(out);
			
			is_action = false;
		}
	};
	
	xmlhttp.open(method, prog, true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send(param);
}

function actionKAJAX(prog, param, out, method, setting) {
	setting = (setting != null && setting !== undefined)? setting : {};
	setting.block = ("block" in setting)? setting.block : true;
	
	if (!is_action) {
		
		if (setting.block) {
			is_action = true;
		}
		
		global_prog = prog;
		global_param = param;
		global_out = out;
		global_method = method;
		
		if (param != null) {
			if (param.tagName == "FORM") {
				param = createParam(param);
			}
		} else {
			param = "";
		}
		
		if (out == null) {
			out = div_body;
		}
		
		if (method == null) {
			method = "POST";
		}
		
		loadXMLDoc(prog, param, out, method);
	}
}

function refreshContext() {
	actionKAJAX(global_prog, global_param, global_out, global_method);
}

function createParam(form) {
	var param='';
	
	inputs = form.getElementsByTagName("input");
	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].type == "text" || inputs[i].type == "password" || inputs[i].type == "hidden") {
			if (param != '') {
				param += "&";
			}
			param += inputs[i].name + "=" + encodeURI(inputs[i].value);
		}
		if (inputs[i].type == "checkbox" && inputs[i].checked) {
			if (param != '') {
				param += "&";
			}
			param += inputs[i].name + "=" + encodeURI(inputs[i].value);
		}
		if (inputs[i].type == "radio" && inputs[i].checked) {
			if (param != '') {
				param += "&";
			}
			param += inputs[i].name + "=" + encodeURI(inputs[i].value);
		}
	}
	
	select_tags = form.getElementsByTagName("select");
	for (var i = 0; i < select_tags.length; i++) {
		if (param != '') {
			param += "&";
		}
		param += select_tags[i].name + "=" + encodeURI(select_tags[i].options[select_tags[i].selectedIndex].value);
	}
	
	return param;
}

function delete_wait(info, info_wait) {
	info.removeChild(info_wait);
}

function getProg(prog) {
	s = prog.split("/");
	name_ext = s[s.length - 1];
	name = name_ext.split(".", 1);
	return name;
}

function evalJS(elementId) {
	var scripts = document.getElementById(elementId).getElementsByTagName('script');
	var code;
	for (var i = 0; i < scripts.length; i++) {
		code =	scripts[i].innerHTML ? scripts[i].innerHTML : 
			scripts[i].text ? scripts[i].text : 
			scripts[i].textContent;
		try {
			eval(code);
		} catch(e) {
			alert(e);
		}
	}
}
