function calcPocketMoney2() {
	var url = "../../modules/forecast/calcPocketMoney2.php";
	
	var startDate = $F('startDate');
	var selectedAccount = $F('selectedAccount');

	var ajax = new Ajax.Request(
		url,
		{
			method: "post",
			postBody: "startDate=" + encodeURI(startDate) + "&selectedAccount=" + encodeURI(selectedAccount),
			onComplete: writePocketMoney2
		}
	);
}

function writePocketMoney2(request) {
	var pocketMoney2Field = $('pocketmoney2');
	pocketMoney2Field.value = request.responseText;	
}

function submitForecast() {
	var url = "../../modules/forecast/calcForecast.php";

	disableDisplays();

	var copyFields = new Array(
		"startDate",
		"endDate",
		"selectedAccount",
		"savingTarget",
		"pocketmoney1",
		"pocketmoney2",
		"lowerLimitBox",
		"upperLimitBox",
		"plannedTransactionsBox",
		"savingTargetBox",
		"pocketMoney1Box",
		"pocketMoney2Box"
	);
	
	var post = "";
	for (var i = 0; i < copyFields.length; i++) {
		post += "&" + copyField(copyFields[i]);
	}
	
	post = post.substr(1);
	
	var ajax = new Ajax.Request(
		url,
		{
			method: "post",
			postBody: post,
			onComplete: writeForecast
		}
	);
}

function copyField(id) {
	if ($F(id)) {
		return id + "=" + encodeURI($F(id));
	} else {
		return;
	}
}

function writeForecast(request) {
	var xml = request.responseXML;
	
	if (xml.getElementsByTagName("error").length != 0) {
		var errors = xml.getElementsByTagName("errors");
		var errorText = "";
		for (var i = 0; i < errors.length; i++) {
			errorText += getXMLText(errors[i]) + "<br />";
		}
		
		var errorDiv = $("errorDiv");
		errorDiv.innerHTML = errorText;
		errorDiv.style.display = "block";
		
		return;
	}

	var insertChart = decodeURIComponent((getXMLText(xml.getElementsByTagName("insertChart")[0])));
	var oldInsert = "";
	while (insertChart != oldInsert) {
		oldInsert = insertChart;
		insertChart = insertChart.replace("+", " ");
	}
	$("flashContainer").innerHTML = insertChart;
	$("flashContainer").style.display = "block";
	
	var accountCurrency = getXMLText(xml.getElementsByTagName("accountCurrency")[0]);

	var dailyPocketMoneyXML = xml.getElementsByTagName("dailyPocketMoneyValue");
	if (dailyPocketMoneyXML.length != 0) {
		var dailyPocketMoneyValue = getXMLText(dailyPocketMoneyXML[0]);
		$("dailyPocketMoneyText").innerHTML = dailyPocketMoneyValue + "&nbsp;" + accountCurrency;
		$("dailyPocketMoneyRow").style.display = "table-row";
	}
	
	var balancedEndDate1XML = xml.getElementsByTagName("balancedEndDate1");
	if (balancedEndDate1XML.length != 0) {
		var balancedEndDate1 = getXMLText(balancedEndDate1XML[0]);
		$("balancedEndDate1Text").innerHTML = balancedEndDate1 + "&nbsp;" + accountCurrency;
		$("balancedEndDate1Row").style.display = "table-row";
	}

	var balancedEndDate2XML = xml.getElementsByTagName("balancedEndDate2");
	if (balancedEndDate2XML.length != 0) {
		var balancedEndDate2 = getXMLText(balancedEndDate2XML[0]);
		$("balancedEndDate2Text").innerHTML = balancedEndDate2 + "&nbsp;" + accountCurrency;
		$("balancedEndDate2Row").style.display = "table-row";
	}
}

function getXMLText(node) {
	if (node.textContent) {
		return node.textContent;
	}
	if (node.text) {
		return node.text;
	}
	if (node.innerHTML) {
		return node.innerHTML;
	}
}

function disableDisplays() {
	$("errorDiv").style.display = "none";
	$("flashContainer").style.display = "none";
	$("dailyPocketMoneyRow").style.display = "none";
	$("balancedEndDate1Row").style.display = "none";
	$("balancedEndDate2Row").style.display = "none";
}