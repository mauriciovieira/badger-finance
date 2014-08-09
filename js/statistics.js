function submitSelect() {
	var url = "../../modules/statistics/statistics.php"

	var form = document.getElementById("selectForm");
	
	var dateFormat = $F("dateFormat");
	var errorMsgAccountMissing = $F("errorMsgAccountMissing");
	var errorMsgStartBeforeEnd = $F("errorMsgStartBeforeEnd");
	var errorMsgEndInFuture = $F("errorMsgEndInFuture");
	
	url += "?mode=" + (form.elements["mode"][0].checked ? "trendData" : "categoryData");
		
	var accountIds = '';
	var first = true;
	var accountArray = dataGridAccountManagerStatistic.getAllSelectedIds();
	
	if (accountArray.length == 0) {
		alert(errorMsgAccountMissing);
		return;
	}
	
	for (i = 0; i < accountArray.length; i++) {
		if (!first) {
			accountIds += ",";
		} else {
			first = false;
		}
		
		accountIds += accountArray[i];
	}
	
	var startDateStr = parseDate(form.startDate.value, dateFormat);
	var endDateStr = parseDate(form.endDate.value, dateFormat);
	
	if (dateCompare(startDateStr, endDateStr) >= 0) {
		alert(errorMsgStartBeforeEnd);
		return;
	}
	
	var now = new Date();
	var nowStr = now.getFullYear() + "-" + (now.getMonth() + 1) + "-" + now.getDate();
	if (dateCompare(endDateStr, nowStr) > 0) {
		alert(errorMsgEndInFuture);
		return;
	}

	url += "&accounts=" + 	accountIds;
	
	url += "&startDate=" + startDateStr;
	
	url += "&endDate=" + endDateStr;
	
	url += "&type=" + (form.elements["type"][0].checked ? "i" : "o");
	
	url += "&summarize=" + (form.elements["summarize"][0].checked ? "t" : "f");

	//alert(url);

	writeFlash(encodeURIComponent(url));
	
	var flashContainer = $("flashContainer");
	window.scrollTo(0, flashContainer.offsetTop);

	return;
}

function writeFlash(url) {
	var container = document.getElementById('flashContainer');

	var objectStart = "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0' width='800' height='400'>";
	var objectEnd = "</object>";
	var paramMovie = "<param name='movie' value='../../includes/charts/charts.swf?library_path=..%2F..%2Fincludes%2Fcharts%2Fcharts_library&php_source=" + url + "' />";
	var paramQuality = "<param name='quality' value='high' />";
	var paramBgcolor = "<param name='bgcolor' value='#ECE9D8' />";
	var paramWmode = "<param name='wmode' value='transparent' />";
	var embed = "<embed src='../../includes/charts/charts.swf?library_path=..%2F..%2Fincludes%2Fcharts%2Fcharts_library&php_source=" + url + "' width='800' height='400' quality='high' pluginspage='http://www.macromedia.com/go/getflashplayer' type='application/x-shockwave-flash' bgcolor='#ECE9D8' wmode='transparent' />";
	
	container.innerHTML = objectStart + paramMovie + paramQuality + paramBgcolor + paramWmode + embed + objectEnd;
}

function parseDate (date, format) {
	var dateFormat = format;
	var formatChar = ' ';
	var aFormat = dateFormat.split(formatChar);
	if (aFormat.length < 3) {
		formatChar = '/';
		aFormat = dateFormat.split(formatChar);
		if (aFormat.length < 3) {
			formatChar = '.';
			aFormat = dateFormat.split(formatChar);
			if (aFormat.length < 3) {
				formatChar = '-';
				aFormat = dateFormat.split(formatChar);
				if (aFormat.length < 3) {
					formatChar = '';					// invalid date format

				}
			}
		}
	}

	var tokensChanged = 0;
	if (formatChar != "") {
		aData =	date.split(formatChar);			// use user's date

		for (i=0; i<3; i++) {
			if ((aFormat[i] == "d") || (aFormat[i] == "dd")) {
				dateSelected = parseInt(aData[i], 10);
				tokensChanged++;
			} else if ((aFormat[i] == "m") || (aFormat[i] == "mm")) {
				monthSelected = parseInt(aData[i], 10);
				tokensChanged++;
			} else if ((aFormat[i] == "yyyy") || (aFormat[i] == "yy")) {
				yearSelected = parseInt(aData[i], 10);
				if (yearSelected < 100) {
					yearSelected += 2000;
				}
				tokensChanged++;
			} else if (aFormat[i] == "mmm") {
				for (j=0; j<12; j++) {
					if (aData[i] == monthName[j]) {
						monthSelected=j;
						tokensChanged++;
					}
				}
			} else if (aFormat[i] == "mmmm") {
				for (j=0; j<12; j++) {
					if (aData[i] == monthName2[j]) {
						monthSelected = j;
						tokensChanged++;
					}
				}
			}
		}
	}
	
	return yearSelected + "-" + (monthSelected < 10 ? "0" : "") + monthSelected + "-" + (dateSelected < 10 ? "0" : "") + dateSelected;
}

function updateDateRange() {
	var month = $F("monthSelect");
	var year = $F("yearSelect");
	
	if (!Number(year)) {
		return;
	}
	
	var format = $F("dateFormat");
	
	var startDateObj = document.getElementsByName("startDate")[0];

	if (month == "fullYear") {
		startDay = "01";
		startMonth = "01";
		startYear = year;
	} else {
		startDay = "01";
		startMonth = (month < 10 ? "0" : "") + month;
		startYear = year;
	}
	//alert(startDay);
	
	var startFormat = format;
	
	startDateObj.value = startFormat.replace("dd", startDay).replace("mm", startMonth).replace("yyyy", startYear).replace("yy", startYear.toString().substr(2, 2));

	var endDateObj = document.getElementsByName("endDate")[0];
	if (month == "fullYear") {
		endDay = "31";
		endMonth = "12";
		endYear = year;
	} else {
		endDay = getLastDay(month, year);
		endMonth = (month < 10 ? "0" : "") + month;
		endYear = year;
	}
	
	var now = new Date();
	var nowStr = now.getFullYear() + "-" + (now.getMonth() + 1) + "-" + now.getDate();
	var endStr = endYear + "-" + endMonth + "-" + endDay;
	if (dateCompare(endStr, nowStr) > 0) {
		endMonth = now.getMonth() + 1;
		endMonth = endMonth.toString();
		if (endMonth.length == 1) {
			endMonth = "0" + endMonth;
		}
		endDay = now.getDate();
		endDay = endDay.toString();
		if (endDay.length == 1) {
			endDay = "0" + endDay;
		}
	}
	
	var endFormat = format;
	endDateObj.value = endFormat.replace("dd", endDay).replace("mm", endMonth).replace("yyyy", endYear).replace("yy", endYear.toString().substr(2, 2));
}

function getLastDay(month, year) {
	var result = 0;
	month = parseInt(month);

	switch (month) {
		case 1: //Jan
		case 3: //Mar
		case 5: //May
		case 7: //Jul
		case 8: //Aug
		case 10: //Oct
		case 12: //Dec
			result = 31;
			break;
		
		case 4: //Apr
		case 6: //Jun
		case 9: //Sep
		case 11: //Nov
			result = 30;
			break;
		
		case 2: //Feb
			if (isLeapYear(year)) {
				result = 29;
				break;
			} else {
				result = 28;
				break;
			}
	}
	
	return result;
}	 

function isLeapYear(year) {
	if (year < 1000) {
		return false;
	}
	if (year < 1582) {
		// pre Gregorio XIII - 1582
		return (year % 4 == 0);
	} else {
		// post Gregorio XIII - 1582
		return ((year % 4 == 0) && (year % 100 != 0)) || (year % 400 == 0);
	}
}

function dateCompare(d1Str, d2Str) {
	var d1 = d1Str.split("-");
	var d2 = d2Str.split("-");
	
	for (i = 0; i < 3; i++) {
		d1[i] = parseInt(d1[i], 10);
		d2[i] = parseInt(d2[i], 10);
	}
	
	if (d1[0] < d2[0]) {
		return -1;
	}
	if (d1[0] > d2[0]) {
		return 1;
	}
	
	if (d1[1] < d2[1]) {
		return -1;
	}
	if (d1[1] > d2[1]) {
		return 1;
	}
	
	if (d1[2] < d2[2]) {
		return -1;
	}
	if (d1[2] > d2[2]) {
		return 1;
	}
	
	return 0;
}