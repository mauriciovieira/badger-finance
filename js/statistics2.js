var currentFilterId = 0;
var currentFilterX;
var baseFilterX;
var graphAjax;

function addFilterLineX() {
	var filterLine = $("filterLineEmpty").innerHTML;
	filterLine = filterLine.replace(/__FILTER_ID__/g, currentFilterId);
	var newDiv = document.createElement("div");
	newDiv.id = "wholeFilterLine" + currentFilterId;
	newDiv.style.clear = "both";
	newDiv.innerHTML = filterLine;
	$("filterContent").appendChild(newDiv);
	currentFilterId++;
}

function setFilterContent(id) {
	var selectedFilter = $F("filterSelect" + id);
	
	if (selectedFilter != 'delete') {
		var filterLine = $(selectedFilter + "Empty").innerHTML;
		filterLine = filterLine.replace(/__FILTER_ID__/g, id);
		$("filterContent" + id).innerHTML = filterLine;
	} else {
		var filterLine = $("wholeFilterLine" + id);
		filterLine.parentNode.removeChild(filterLine);
	}
}

function applyFilterX() {
	savePageSetting(true);

	emptyFilterX();
	
	for (var currentId = 0; currentId < currentFilterId; currentId++) {
		if ($("filterSelect" + currentId)) {
			var currentFilterType = $F("filterSelect" + currentId);
			switch (currentFilterType) {
				case "title":
				case "description":
				case "valutaDate":
				case "amount":
				case "transactionPartner":
					if ($F(currentFilterType + currentId) != "") {
						addFilterX(currentFilterType, $F(currentFilterType + "Operator" + currentId), $F(currentFilterType + currentId));
					}
					break;
				
				case "valutaDateBetween":
					if ($F("valutaDateStart" + currentId) != "" && $F("valutaDateEnd" + currentId) != "") {
						addFilterX("valutaDate", "ge", $F("valutaDateStart" + currentId));
						addFilterX("valutaDate", "le", $F("valutaDateEnd" + currentId));
					}
					break;
				
				case "valutaDateAgo":
					if (parseInt($F("valutaDateAgo" + currentId)) > 0) {
						var now = new Date();
						var ago = new Date(now.getTime() - parseInt($F("valutaDateAgo" + currentId)) * 24 * 60 * 60 * 1000);

						addFilterX("valutaDate", "ge", formatDate(ago));
						addFilterX("valutaDate", "lt", formatDate(now));
					}
					break;
				
				case "category":
					if ($F("categoryOp" + currentId) || $F("categoryOp" + currentId + "_0")) {
						var operator;
						
						if ($F("categoryOp" + currentId) == "eq") {
							operator = "eq";
						} else {
							operator = "ne";
						}
						
						if (parseInt($F("categoryId" + currentId)) != 0) {
							var field;
							var id;
							if ($F("categoryId" + currentId).substr(0, 1) == '-') {
								field = "parentCategoryId";
								id = parseInt($F("categoryId" + currentId)) * -1;
							} else {
								field = "categoryId";
								id = parseInt($F("categoryId" + currentId));
							}
							addFilterX(field, operator, id);
						}
					}
					break;
				
				case "outsideCapital":
				case "exceptional":
				case "periodical":
					var valTrue = $F(currentFilterType + currentId);
					var valFalse = $F(currentFilterType + currentId + "_0"); 

					var val = null;

					if (valTrue == "1") {
						val = 1;
					}
					if (valFalse == "0") {
						val = 0;
					}

					if (val !== null) {
						addFilterX(currentFilterType, "eq", val);
					}
					break;
			} //switch
		} //if filterSelect
	} //for currentId
	
	showGraph();
	saveBaseFilter();
	setDGResultAccounts(getSelectedAccountIds());
	updateDGResult();
}

function formatDate(date) {
	var dateFormat = $F("dateFormat");
	
	var dateString = dateFormat;
	var day = "" + (date.getDay() < 10 ? "0" : "") + date.getDay();
	var month = "" + (date.getMonth() + 1 < 10 ? "0" : "") + (date.getMonth() + 1);
	var year = "" + date.getFullYear();
	dateString = dateString.replace(/dd/, day);
	dateString = dateString.replace(/mm/, month);
	dateString = dateString.replace(/yyyy/, year);
	dateString = dateString.replace(/yy/, year.substr(2, 2));
	
	return dateString;
}

function emptyFilterX() {
	currentFilterX = new Array();
}

function addFilterX(field, operator, value) {
	currentFilterX.push({
		"field" : field,
		"operator" : operator,
		"value" : value
	});
}

function saveBaseFilter() {
	baseFilterX = currentFilterX.concat();
}

function resetBaseFilter() {
	currentFilterX = baseFilterX.concat();
}

function getSelectedAccountIds() {
	var accountIdArr = $("dataGridStatistics2Accounts").obj.getAllSelectedIds();
	var accountIds = "";
	for (i = 0; i < accountIdArr.length; i++) {
		accountIds += accountIdArr[i] + ",";
	}
	accountIds = accountIds.substr(0, accountIds.length - 1);
	
	return accountIds;
}

function setDGResultAccounts(accountIds) {
	$("dataGridStatistics2Result").obj.sourceXML = "../../core/XML/getDataGridXML.php?q=MultipleAccounts&qp=" + accountIds;
}

function updateDGResult() {
	$("resultGridContainer").style.display = "block";
	$("dataGridStatistics2Result").obj.filter.reset();

	for (var i = 0; i < currentFilterX.length; i++) {
		$("dataGridStatistics2Result").obj.filter.addFilterCriteria(currentFilterX[i]["field"], currentFilterX[i]["operator"], currentFilterX[i]["value"]);
	}
	
	$("dataGridStatistics2Result").obj.loadData();
}

function serializeParameterX() {
	var result = "";
	
	for (var i = 0; i < currentFilterX.length; i++) {
		result += "&fk" + i + "=" + encodeURI(currentFilterX[i]["field"])
			+ "&fo" + i + "=" + encodeURI(currentFilterX[i]["operator"])
			+ "&fv" + i + "=" + encodeURI(currentFilterX[i]["value"]);
	}
	
	result = result.substr(1);
	
	return result;
}

function showGraph() {
	$("graphContent").innerHTML = "";
	
	if ($F("outputSelectionType") == "Trend") {
		showTrendGraph();
	} else if ($F("outputSelectionType_0") == "Category") {
		showCategoryGraph();
	} else {
		showTimespanGraph();
	}
}

function showTrendGraph() {
	var start;
	var ticks;
	
	if ($F("outputSelectionTrendStart") == "b") {
		start = "b";
	} else {
		start = "0";
	}
	
	if ($F("outputSelectionTrendTicks") == "s") {
		ticks = "s";
	} else {
		ticks = "h";
	}
	
	loadGraph("trend.php?accounts=" + getSelectedAccountIds() + "&start=" + start + "&ticks=" + ticks + "&" + serializeParameterX());
}

function showCategoryGraph() {
	var type;
	var summarize;

	if ($F("outputSelectionCategoryType") == "i") {
		type = "i";
		addFilterX("amount", "ge", 0);
	} else {
		type = "o";
		addFilterX("amount", "le", 0);
	}
	
	if ($F("outputSelectionCategorySummarize") == "t") {
		summarize = "t";
	} else {
		summarize = "f";
	}
	
	loadGraph("category.php?accounts=" + getSelectedAccountIds() + "&type=" + type + "&summarize=" + summarize + "&" + serializeParameterX());
}

function showTimespanGraph() {
	var type;
	var summarize;
	
	if ($F("outputSelectionTimespanType") == "w") {
		type = "w";
	} else if ($F("outputSelectionTimespanType_0") == "m") {
		type = "m";
	} else if ($F("outputSelectionTimespanType_1") == "q") {
		type = "q";
	} else {
		type = "y";
	}
	
	if ($F("outputSelectionTimespanSummarize") == "t") {
		summarize = "t";
	} else {
		summarize = "f";
	}
	
	loadGraph("timespan.php?accounts=" + getSelectedAccountIds() + "&type=" + type + "&summarize=" + summarize + "&" + serializeParameterX());
}

function loadGraph(url) {
	if (graphAjax) {
		//How to do that?
		//graphAjax.stop();
	}
	
	graphAjax = new Ajax.Request(
		url,
		{
			onComplete: displayGraph
		}
	);
}

function displayGraph(request) {
	var graphArea = $("graphContent");
	
	graphArea.innerHTML = request.responseText;
}

function updateOutputSelection() {
	var sourceName;
	if ($F("outputSelectionType") == "Trend") {
		sourceName = "outputSelectionTrend";
	} else if ($F("outputSelectionType_0") == "Category") {
		sourceName = "outputSelectionCategory";
	} else {
		sourceName = "outputSelectionTimespan";
	}
	var source = $(sourceName).innerHTML;
	
	var target = $("outputSelectionContent");
	target.innerHTML = source.replace(/__ACTIVE_OS__/g, "");
}

function reachThroughTrend(date, accountIds) {
	setDGResultAccounts(accountIds);
	resetBaseFilter();
	addFilterX("valutaDate", "eq", date);
	updateDGResult();
}

function reachThroughCategory(categoryId) {
	var field;

	if ($F("outputSelectionCategorySummarize") == "t") {
		field = "parentCategoryId";
	} else {
		field = "categoryId";
	}
	resetBaseFilter();
	addFilterX(field, "eq", categoryId);
	updateDGResult();
}

function reachThroughTimespan(begin, end, categoryId) {
	var field;
	if ($F("outputSelectionTimespanSummarize") == "t") {
		field = "parentCategoryId";
	} else {
		field = "categoryId";
	}
	resetBaseFilter();
	addFilterX(field, "eq", categoryId);
	addFilterX("valutaDate", "ge", begin);
	addFilterX("valutaDate", "le", end);
	updateDGResult();
}

////////////////////////
// SaveSettings
////////////////////////

var pageSettings = new PageSettings();

function loadPageSettingNamesList() {
	pageSettings.getSettingNamesList("showPageSettingNamesList", "statistics2User");
}

function showPageSettingNamesList(settingNamesList) {
	addPageSetting("");
	
	for (var i = 0; i < settingNamesList.length; i++) {
		addPageSetting(settingNamesList[i]);
	}
}

function addPageSetting(settingName) {
	var select = $("pageSettingsSelect");

	var option = document.createElement("option");
//	option.value = ("" + settingName).replace(/'/g, "'");
	var value = document.createAttribute("value");
	value.nodeValue = settingName;
	option.setAttributeNode(value);
	option.innerHTML = settingName;
	select.appendChild(option);
}

function loadPageSetting(loadDefault) {
	var page;
	var settingName;

	if (!loadDefault) {	
		page = "statistics2User";
		settingName = $F("pageSettingsSelect");
	} else {
		page = "statistics2Default";
		settingName = "default";
	}
	
	if (settingName != "") {
		pageSettings.getSettingSer("showPageSetting", page, settingName);
	}
}

function showPageSetting(settings) {
	if (!(settings instanceof Object)) {
		return;
	}
	
	$("filterContent").innerHTML = "";
	
	largestFilterId = settings["largestFilterId"];
	currentFilterId = 0;
	
	for (var i = 0; i <= largestFilterId; i++) {
		if (settings["filters"]["filter" + i]) {
			addFilterLineX();
			var lastFilterId = currentFilterId - 1;
			var type = settings["filters"]["filter" + i]["type"];
			$("filterSelect" + lastFilterId).value = type;
			setFilterContent(lastFilterId);
			switch (type) {
				case "title":
				case "description":
				case "valutaDate":
				case "amount":
				case "transactionPartner":
					$(type + lastFilterId).value = settings["filters"]["filter" + i][type + "Value"]
					$(type + "Operator" + lastFilterId).value = settings["filters"]["filter" + i][type + "Operator"];
					break;
				
				case "valutaDateBetween":
					$("valutaDateStart" + lastFilterId).value = settings["filters"]["filter" + i]["valutaDateStart"];
					$("valutaDateEnd" + lastFilterId).value = settings["filters"]["filter" + i]["valutaDateEnd"];
					break;
				
				case "valutaDateAgo":
					$("valutaDateAgo" + lastFilterId).value = settings["filters"]["filter" + i]["valutaDateAgo"];
					break;
				
				case "category":
					if (settings["filters"]["filter" + i]["categoryOp"] == "eq") {
						$("categoryOp" + lastFilterId).checked = true;
					} else {
						$("categoryOp" + lastFilterId + "_0").checked = true;
					}
					$("categoryId" + lastFilterId).value = settings["filters"]["filter" + i]["categoryId"];
					break;
				
				case "outsideCapital":
				case "exceptional":
				case "periodical":
					if (settings["filters"]["filter" + i][type + "Checked"]) {
						$(type + lastFilterId).checked = true;
					} else {
						$(type + lastFilterId + "_0").checked = true; 
					}
					break;
			} //switch type
		} // if settings exist
	} //for all filters
	
	dgGrid = $("dataGridStatistics2Accounts").obj;
	dgGrid.deselectAllRows();
	for (var i = 0; i < settings["accountIds"].length; i++) {
		dgGrid.preselectId(settings["accountIds"][i]);
	}
	
	switch (settings["graphType"]) {
		case "Trend":
			$("outputSelectionType").checked = true;
			updateOutputSelection();
			if (settings["graphOptions"]["trendStart"] == "zero") {
				$("outputSelectionTrendStart_0").checked = true;
			} else {
				$("outputSelectionTrendStart").checked = true;
			}
			if (settings["graphOptions"]["trendTicks"] == "show") {
				$("outputSelectionTrendTicks").checked = true;
			} else {
				$("outputSelectionTrendTicks_0").checked = true;
			}
			break;
		case "Category":
			$("outputSelectionType_0").checked = true;
			updateOutputSelection();
			if (settings["graphOptions"]["categoryType"] == "input") {
				$("outputSelectionCategoryType").checked = true;
			} else {
				$("outputSelectionCategoryType_0").checked = true;
			}
			if (settings["graphOptions"]["categorySummarize"] == true) {
				$("outputSelectionCategorySummarize").checked = true;
			} else {
				$("outputSelectionCategorySummarize_0").checked = true;
			}
			break;
		case "Timespan":
			$("outputSelectionType_1").checked = true;
			updateOutputSelection();
			switch (settings["graphOptions"]["timespanType"]) {
				case "week":
					$("outputSelectionTimespanType").checked = true;
					break;
				case "month":
					$("outputSelectionTimespanType_0").checked = true;
					break;
				case "quarter":
					$("outputSelectionTimespanType_1").checked = true;
					break;
				case "year":
					$("outputSelectionTimespanType_2").checked = true;
					break;
			}
			if (settings["graphOptions"]["trendSummarize"] == true) {
				$("outputSelectionTrendSummarize").checked = true;
			} else {
				$("outputSelectionTrendSummarize_0").checked = true;
			}
			break;
	} //switch graphType
} //function showPageSetting

function savePageSetting(saveDefault) {
	var page;

	if (!saveDefault) {	
		page = "statistics2User";
	} else {
		page = "statistics2Default";
	}
	
	var selectedSetting = $F("pageSettingsSelect");

	var settings = new Object();

	settings["largestFilterId"] = currentFilterId - 1;
	
	settings["filters"] = new Object();
	for (var i = 0; i < currentFilterId; i++) {
		if ($("filterSelect" + i)) {
			settings["filters"]["filter" + i] = new Object();
			var type = $("filterSelect" + i).value;
			settings["filters"]["filter" + i]["type"] = type;
			switch (type) {
				case "title":
				case "description":
				case "valutaDate":
				case "amount":
				case "transactionPartner":
					settings["filters"]["filter" + i][type + "Value"] = $(type + i).value;
					settings["filters"]["filter" + i][type + "Operator"] = $(type + "Operator" + i).value;
					break;
				
				case "valutaDateBetween":
					settings["filters"]["filter" + i]["valutaDateStart"] = $("valutaDateStart" + i).value;
					settings["filters"]["filter" + i]["valutaDateEnd"] = $("valutaDateEnd" + i).value;
					break;
				
				case "valutaDateAgo":
					settings["filters"]["filter" + i]["valutaDateAgo"] = $("valutaDateAgo" + i).value;
					break;
				
				case "category":
					if ($("categoryOp" + i).checked) {
						 settings["filters"]["filter" + i]["categoryOp"] = "eq";
					} else {
						 settings["filters"]["filter" + i]["categoryOp"] = "ne";
					}
					settings["filters"]["filter" + i]["categoryId"] = $("categoryId" + i).value;
					break;
				
				case "outsideCapital":
				case "exceptional":
				case "periodical":
					if ($(type + i).checked) {
						settings["filters"]["filter" + i][type + "Checked"] = true;
					} else {
						settings["filters"]["filter" + i][type + "Checked"] = false; 
					}
					break;
			} //switch type
		} // if settings exist
	} //for all filters
	
	settings["accountIds"] = $("dataGridStatistics2Accounts").obj.getAllSelectedIds();
	
	settings["graphOptions"]= new Object();
	if ($F("outputSelectionType") == "Trend") {
		settings["graphType"] = "Trend";
		if ($F("outputSelectionTrendStart") == "b") {
			settings["graphOptions"]["trendStart"] == "balance";
		} else {
			settings["graphOptions"]["trendStart"] = "zero";
		}
		
		if ($F("outputSelectionTrendTicks") == "s") {
			settings["graphOptions"]["trendTicks"] = "show";
		} else {
			settings["graphOptions"]["trendTicks"] = "hide";
		}
	} else if ($F("outputSelectionType_0") == "Category") {
		settings["graphType"] = "Category";
		if ($F("outputSelectionCategoryType") == "i") {
			settings["graphOptions"]["categoryType"] = "input";
		} else {
			settings["graphOptions"]["categoryType"] = "output";
		}
		
		if ($F("outputSelectionCategorySummarize") == "t") {
			settings["graphOptions"]["categorySummarize"] = true;
		} else {
			settings["graphOptions"]["categorySummarize"] = false;
		}
	} else {
		settings["graphType"] = "Timespan";
		if ($F("outputSelectionTimespanType") == "w") {
			settings["graphOptions"]["timespanType"] = "week";
		} else if ($F("outputSelectionTimespanType_0") == "m") {
			settings["graphOptions"]["timespanType"] = "month";
		} else if ($F("outputSelectionTimespanType_1") == "q") {
			settings["graphOptions"]["timespanType"] = "quarter";
		} else {
			settings["graphOptions"]["timespanType"] = "year";
		}
		
		if ($F("outputSelectionTimespanSummarize") == "t") {
			settings["graphOptions"]["trendSummarize"] = true;
		} else {
			settings["graphOptions"]["trendSummarize"] = false;
		}
	} //switch graphType

	if (!saveDefault) {
		var settingName = prompt(newNamePrompt, selectedSetting);

		if (settingName) {
			pageSettings.setSettingSer("statistics2User", settingName, settings);
			if (settingName != selectedSetting) {
				addPageSetting(settingName);
			}
		}	
	} else {
		pageSettings.setSettingSer("statistics2Default", "default", settings);
	}
	
}

function deletePageSetting() {
	var setting = $F("pageSettingsSelect");
	
	if (setting == "") {
		return;
	}

	pageSettings.deleteSetting("statistics2User", setting);
	var select = $("pageSettingsSelect");
	
	var currentOption = select.firstChild;
	do {
		if (currentOption.value == setting) {
			select.removeChild(currentOption);
			break;
		}
	} while(currentOption = currentOption.nextSibling)
}