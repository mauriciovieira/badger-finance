PageSettings = Class.create();

PageSettings.prototype = {
	initialize: function() {
		
	},
	
	getSettingNamesList: function(callback, page) {
		this.callAjax(function(XHR) {eval(callback + "(" + XHR.responseText + ")")}, "getSettingNamesList", page);
	},
	
	getSettingRaw: function(callback, page, settingName) {
		this.callAjax(function(XHR) {eval(callback + "(\"" + XHR.responseText + "\")")}, "getSettingRaw", page, settingName);
	},
	
	setSettingRaw: function(page, settingName, setting) {
		this.callAjax(null, "setSettingRaw", page, settingName, setting);
	},

	getSettingSer: function(callback, page, settingName) {
		this.callAjax(function(XHR) {eval(callback + "(" + XHR.responseText + ")");}, "getSettingSer", page, settingName);
	},

	setSettingSer: function(page, settingName, setting) {
		this.callAjax(null, "setSettingSer", page, settingName, setting.toJSONString());
	},
	
	deleteSetting: function(page, settingName) {
		this.callAjax(null, "deleteSetting", page, settingName);
	},
	
	callAjax: function(callback, action, page, settingName, setting, async) {
		if (async !== false) {
			async = true;
		}
//		if (this.myAjax) {
//			this.myAjax.transport.abort();
//		}
		
		this.myAjax = new Ajax.Request(
			BADGER_GET_PAGE_SETTINGS_URI,
			{
				method: "post",
				parameters: "action=" + action
					+ "&page=" + encodeURIComponent(page)
					+ "&settingName=" + encodeURIComponent(settingName)
					+ "&setting=" + encodeURIComponent(setting)
				,
				onComplete: callback,
				asynchronous: async
			}
		);
		if (!async) {
			return this.myAjax.transport.responseText;
		}
	},
	getSettingSync: function(page, settingName) {
		return this.callAjax(null, "getSettingSer", page, settingName, false, false);
	}
	
}