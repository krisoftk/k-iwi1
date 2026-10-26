/** Version 2.0 **/

var div_body = null;
var div_information = null;
var loader_content = null;
var error_page = "404: Page not found<br>";

//save_history
var global_prog, global_param, global_out, global_method;

//for stop double click
var is_action = false;

function initializeKAJAX(body, info, loader) {
	div_body = body;
	div_information = info;
	loader_content = loader;
};

function actionKAJAX(program, param, context, method, actionSettings) {
	actionSettings = (actionSettings != null && actionSettings !== undefined)? actionSettings : {};
	actionSettings.block = ("block" in actionSettings)? actionSettings.block : true;
	actionSettings.dataType = ("dataType" in actionSettings)? actionSettings.dataType : false;
	actionSettings.beforeSend = ("beforeSend" in actionSettings)? actionSettings.beforeSend : false;
	actionSettings.complete = ("complete" in actionSettings)? actionSettings.complete : false;
	actionSettings.error = ("error" in actionSettings)? actionSettings.error : false;
	actionSettings.success = ("success" in actionSettings)? actionSettings.success : false;
	
	if (!is_action) {
		
		if (actionSettings.block) {
			is_action = true;
		}
		
		global_prog = program;
		global_param = param;
		global_out = context;
		global_method = method;
		
		requestAjax = {};
		requestAjax.url = program;
		
		if (param !== undefined || param != null || param != "") {
			requestAjax.data = param;
		}
		
		if (context === undefined || context == null || context == 'default' || context == '') {
			requestAjax.context = $(div_body);
		} else {
			requestAjax.context = $(context);
		}
		
		if (method === undefined || method == null || method == '') {
			requestAjax.type = 'post';
		} else {
			requestAjax.type = method;
		}
		
		if (actionSettings.dataType) {
			requestAjax.dataType = actionSettings.dataType;
		}
		
		requestAjax.beforeSend = function(jqXHR, settings) {
			if (div_information != null) {
				info = $(div_information);
				
				if (loader_content != null) {
					info.html(loader_content);
				} else {
					info.html('<div id="loading"></div>');
				}
			}
			
			if (actionSettings.beforeSend) {
				actionSettings.beforeSend(jqXHR, settings);
			}
		};
		
		requestAjax.complete = function(jqXHR, textStatus) {
			is_action = false;
			
			if (div_information != null) {
				$('#loading').remove();
			}
			
			if (actionSettings.complete) {
				actionSettings.complete(jqXHR, textStatus);
			}
		};
		
		requestAjax.error = function(jqXHR, textStatus, errorThrown) {
			is_action = false;
			
			if (actionSettings.error) {
				actionSettings.error(jqXHR, textStatus, errorThrown);
			}
		};
		
		requestAjax.success = function(data, textStatus, jqXHR) {
			if (data.timeout) {
				$(div_body).html('<script type="text/javascript"><!--location.href = "' + data.location + '";	//--></script>');
			} else {
				if (actionSettings.success) {
					actionSettings.success(data, textStatus, jqXHR);
				} else {
					$(this).html(data);
				}
			}
		};
		
		$.ajax(requestAjax);
	}
}

function refreshContext() {
	actionKAJAX(global_prog, global_param, global_out, global_method);
}