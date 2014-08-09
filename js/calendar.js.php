<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://www.badger-finance.org 
*
**/
define("BADGER_ROOT", "..");
require_once(BADGER_ROOT . "/includes/fileHeaderBackEnd.inc.php");
//require_once(BADGER_ROOT . "/core/UserSettings.class.php"); // sollte das nicht auch in die Includes??

header('Content-Type: text/javascript');
$badgerRoot = getGPC($_GET, 'badgerRoot'); //we need this bagerRoot for images path

$settings = new UserSettings($badgerDb);
$badgerTemplate = $settings->getProperty("badgerTemplate");
  
//We do our best to get this cached
//header('Cache-Control: public');
//header('Expires: ' . date('r', time() + 24 * 60 * 60));
?> 
//	written	by Tan Ling Wee
//	last updated 28 July 2003
//	email :	info@sparrowscripts.com
//	modified by ALQUANTO 30 July 2003 - german language included.
//									  - modified languageLogic with the ISO-2letter-strings
//									  - changes in in showCalendar: defaultLanguage is already set...
//									  - js and html corrected... more xhtml-compliant... simplier css
//	email: popcalendar@alquanto.de
//
//	modified by PinoToy 25 July 2003  - new logic for multiple languages (English, Spanish and ready for more).
//									  - changes in popUpMonth & popDownMonth methods for hidding	popup.
//									  - changes in popDownYear & popDownYear methods for hidding	popup.
//									  - new logic for disabling dates in	the past.
//									  - new method showCalendar, dynamic	configuration of language, enabling	past & position.
//									  - changes in the styles.
//	email  : pinotoy@yahoo.com
//
//	modified by Eni Kao 24 August 2005
//		- Optimized multiple language support with more than one calendar in different languages (as toy.html)
//		- Added support for Opera, Konqueror and Firefox in non-Quicks mode
//		- Fully compatible to XHTML 1.0 Transitional
//		- Changed depricated font, bgcolor etc. to CSS attributes
//		- Added messages as tooltips (and a new one for the close button)
//		- Made ESC work in every browser
//		- Changed german umlauts to HTML entities for better support of international character sets
//		- TODO: Do this with spanish special characters
//		- Perhaps I missed something?
//	email: popcalendar@enikao.net
//
//  modified by sepp (BADGER Team)
//  - language support disabled -> generated dynamically by php from a DB
//  - some onLoad-bugs fixed
//  - styles extracted

	var enablePast = 0;		// 0 - disabled ; 1 - enabled
	var fixedX = -1;		// x position (-1 if to appear below control)
	var fixedY = -1;		// y position (-1 if to appear below control)
	var startAt = 1;		// 0 - sunday ; 1 - monday -> Usereinstellung
	var showWeekNumber = 1;	// 0 - don't show; 1 - show
	var showToday = 1;		// 0 - don't show; 1 - show
	var imgDir = '<?php 
		$imgDir = "$badgerRoot/tpl/$badgerTemplate/Widgets/calendar";
		if (!file_exists($imgDir)) {
			$imgDir = "$badgerRoot/tpl/Standard/Widgets/calendar";
		}
		
	 echo $imgDir;
	?>/';
	var dayName = '';

	var gotoString = '<?php echo getBadgerTranslation2("Calendar","gotoString")?>';
	var todayString = '<?php echo getBadgerTranslation2("Calendar","todayString")?>';
	var weekString = '<?php echo getBadgerTranslation2("Calendar","weekString")?>';
	var scrollLeftMessage = '<?php echo getBadgerTranslation2("Calendar","scrollLeftMessage")?>';
	var scrollRightMessage = '<?php echo getBadgerTranslation2("Calendar","scrollRightMessage")?>';
	var selectMonthMessage = '<?php echo getBadgerTranslation2("Calendar","selectMonthMessage")?>';
	var selectYearMessage = '<?php echo getBadgerTranslation2("Calendar","selectYearMessage")?>';
	var selectDateMessage = '<?php echo getBadgerTranslation2("Calendar","selectDateMessage")?>';
	var closeCalendarMessage = '<?php echo getBadgerTranslation2("Calendar","closeCalendarMessage")?>';
	var	monthName = <?php echo str_replace("\\", "", getBadgerTranslation2("Calendar","monthName"))?>;
	var	monthName2 = <?php echo str_replace("\\", "", getBadgerTranslation2("Calendar","monthName2"))?>;
	if (startAt==0) {
		dayName = <?php echo str_replace("\\", "", getBadgerTranslation2("Calendar","dayNameStartsWithSunday"))?>;
	} else {
		dayName = <?php echo str_replace("\\", "", getBadgerTranslation2("Calendar","dayNameStartsWithMonday"))?>;
	}

	var crossobj, crossMonthObj, crossYearObj, monthSelected, yearSelected, dateSelected, omonthSelected, oyearSelected, odateSelected, monthConstructed, yearConstructed, intervalID1, intervalID2, timeoutID1, timeoutID2, ctlToPlaceValue, ctlNow, dateFormat, nStartingYear, selDayAction, isPast;
	var visYear  = 0;
	var visMonth = 0;
	var bPageLoaded = false;
	var ie  = document.all;
	var dom = document.getElementById;
	var ns4 = document.layers;
	var op = window.opera
	var today    = new Date();
	var dateNow  = today.getDate();
	var monthNow = today.getMonth();
	var yearNow  = today.getFullYear();
	var imgsrc   = new Array('drop1.png','drop2.png','left1.png','left2.png','right1.png','right2.png');
	var img      = new Array();
	var bShow    = false;

	/* hides <select> and <applet> objects (for IE only) */
	function hideElement( elmID, overDiv ) {
		if(ie) {
			for(i = 0; i < document.all.tags( elmID ).length; i++) {
				obj = document.all.tags( elmID )[i];
				if(!obj || !obj.offsetParent) continue;

				// Find the element's offsetTop and offsetLeft relative to the BODY tag.
				objLeft   = obj.offsetLeft;
				objTop    = obj.offsetTop;
				objParent = obj.offsetParent;

				while(objParent.tagName.toUpperCase() != 'BODY') {
					objLeft  += objParent.offsetLeft;
					objTop   += objParent.offsetTop;
					objParent = objParent.offsetParent;
				}

				objHeight = obj.offsetHeight;
				objWidth  = obj.offsetWidth;

				if((overDiv.offsetLeft + overDiv.offsetWidth) <= objLeft);
				else if((overDiv.offsetTop + overDiv.offsetHeight) <= objTop);
				/* CHANGE by Charlie Roche for nested TDs*/
				else if(overDiv.offsetTop >= (objTop + objHeight + obj.height));
				/* END CHANGE */
				else if(overDiv.offsetLeft >= (objLeft + objWidth));
				else {
					obj.style.visibility = 'hidden';
				}
			}
		}
	}

	/*
	* unhides <select> and <applet> objects (for IE only)
	*/
	function showElement(elmID) {
		if(ie) {
			for(i = 0; i < document.all.tags( elmID ).length; i++) {
				obj = document.all.tags(elmID)[i];
				if(!obj || !obj.offsetParent) continue;
				obj.style.visibility = '';
			}
		}
	}

	function HolidayRec (d, m, y, desc) {
		this.d = d;
		this.m = m;
		this.y = y;
		this.desc = desc;
	}

	var HolidaysCounter = 0;
	var Holidays = new Array();

	function addHoliday (d, m, y, desc) {
		Holidays[HolidaysCounter++] = new HolidayRec (d, m, y, desc);
	}

	if (dom) {
		for	(i=0;i<imgsrc.length;i++) {
			img[i] = new Image;
			img[i].src = imgDir + imgsrc[i];
		}
		
		document.write (
			'<div onclick="bShow=true" id="calendar">' +
				'<table width="'+((showWeekNumber==1)?250:220)+'">' +
					'<tr class="calendarHead"><td>' + 
						'<table width="100%"><tr>' +
							'<td class="CALCaption"><span id="CALCaption"></span></td>' + 
							'<td align="right"><a href="javascript:hideCalendar()" id="imgCloseCalendar">' +
							   '<img src="'+imgDir+'close.gif" width="15" height="13" border="0" title="'+closeCalendarMessage+'" alt="'+closeCalendarMessage+'" /></a></td>' +
						'</tr></table>' +
					'</td></tr>' +
					'<tr><td class="CALContent"><div id="CALContent"></div></td></tr>');
		
		
		if (showToday == 1) {
			document.write ('<tr class="CALTodayString"><td><span id="lblToday">_</span></td></tr>');
		}
			
		
		document.write ('</table></div><div id="selectMonth"></div><div id="selectYear"></div>');
	}

	var	styleLightBorder = 'border:1px solid #a0a0a0;';

	function swapImage(srcImg, destImg) {
		if (ie) document.getElementById(srcImg).setAttribute('src',imgDir + destImg);
	}

	
	function initCalendar() {
		if (!ns4) {
			crossobj=(dom)?document.getElementById('calendar').style : ie? document.all.calendar : document.calendar;
			hideCalendar();

			crossMonthObj = (dom) ? document.getElementById('selectMonth').style : ie ? document.all.selectMonth : document.selectMonth;

			crossYearObj = (dom) ? document.getElementById('selectYear').style : ie ? document.all.selectYear : document.selectYear;

			monthConstructed = false;
			yearConstructed = false;

			if (showToday == 1) {
				document.getElementById('lblToday').innerHTML =	todayString + '&nbsp;<a onmousemove="window.status=\''+gotoString+'\'" onmouseout="window.status=\'\'" title="'+gotoString+'" href="javascript:monthSelected=monthNow;yearSelected=yearNow;constructCalendar();">'+dayName[(today.getDay()-startAt==-1)?6:(today.getDay()-startAt)]+', ' + dateNow + ' ' + monthName[monthNow] + ' ' + yearNow + '</a>';
			}

			bPageLoaded=true;
		}
	}

	function constructCaption() {
		if (!ns4) {
			sHTML1 = '<span id="spanLeft" class="spanFilterBox" onmouseover="swapImage(\'changeLeft\',\'left2.png\');this.className=\'spanActive\';window.status=\''+scrollLeftMessage+'\'" onclick="decMonth()" onmouseout="clearInterval(intervalID1);swapImage(\'changeLeft\',\'left1.png\');this.className=\'spanFilterBox\';window.status=\'\';clearTimeout(timeoutID1)" onmousedown="clearTimeout(timeoutID1);timeoutID1=setTimeout(\'StartDecMonth()\',500)" onmouseup="clearTimeout(timeoutID1);clearInterval(intervalID1)" title="'+scrollLeftMessage+'">&nbsp<img id="changeLeft" src="'+imgDir+'left1.png" width="10" height="11" border="0" alt="'+scrollLeftMessage+'" />&nbsp</span>&nbsp;';
			sHTML1 += '<span id="spanRight" class="spanFilterBox" onmouseover="swapImage(\'changeRight\',\'right2.png\');this.className=\'spanActive\';window.status=\''+scrollRightMessage+'\'" onmouseout="clearInterval(intervalID1);swapImage(\'changeRight\',\'right1.png\');this.className=\'spanFilterBox\';window.status=\'\';clearTimeout(timeoutID1)" onclick="incMonth()" onmousedown="clearTimeout(timeoutID1);timeoutID1=setTimeout(\'StartIncMonth()\',500)" onmouseup="clearTimeout(timeoutID1);clearInterval(intervalID1)" title="'+scrollRightMessage+'">&nbsp<img id="changeRight" src="'+imgDir+'right1.png" width="10" height="11" border="0" alt="'+scrollRightMessage+'" />&nbsp</span>&nbsp;';
			sHTML1 += '<span id="spanMonth" class="spanFilterBox" onmouseover="swapImage(\'changeMonth\',\'drop2.png\');this.className=\'spanActive\';window.status=\''+selectMonthMessage+'\'" onmouseout="swapImage(\'changeMonth\',\'drop1.png\');this.className=\'spanFilterBox\';window.status=\'\'" onclick="popUpMonth()" title="'+selectMonthMessage+'"></span>&nbsp;';
			sHTML1 += '<span id="spanYear" class="spanFilterBox" onmouseover="swapImage(\'changeYear\',\'drop2.png\');this.className=\'spanActive\';window.status=\''+selectYearMessage+'\'" onmouseout="swapImage(\'changeYear\',\'drop1.png\');this.className=\'spanFilterBox\';window.status=\'\'" onclick="popUpYear()" title="'+selectYearMessage+'"></span>&nbsp;';
	
			document.getElementById('CALCaption').innerHTML = sHTML1;

			//Hack to adjust close button message
			closeButtonImg = document.getElementById('imgCloseCalendar');

			if (closeButtonImg) {
				closeButtonImg.innerHTML = '<img src="'+imgDir+'close.gif" width="15" height="13" border="0" title="'+closeCalendarMessage+'" alt="'+closeCalendarMessage+'" />';
			}
		}
	}

	function hideCalendar() {
		crossobj.visibility = 'hidden';
		if (crossMonthObj != null) crossMonthObj.visibility = 'hidden';
		if (crossYearObj  != null) crossYearObj.visibility = 'hidden';
		showElement('SELECT');
		showElement('APPLET');
	}

	function padZero(num) {
		return (num	< 10) ? '0' + num : num;
	}

	function constructDate(d,m,y) {
		sTmp = dateFormat;
		sTmp = sTmp.replace ('dd','<e>');
		sTmp = sTmp.replace ('d','<d>');
		sTmp = sTmp.replace ('<e>',padZero(d));
		sTmp = sTmp.replace ('<d>',d);
		sTmp = sTmp.replace ('mmmm','<p>');
		sTmp = sTmp.replace ('mmm','<o>');
		sTmp = sTmp.replace ('mm','<n>');
		sTmp = sTmp.replace ('m','<m>');
		sTmp = sTmp.replace ('<m>',m+1);
		sTmp = sTmp.replace ('<n>',padZero(m+1));
		sTmp = sTmp.replace ('<o>',monthName[m]);
		sTmp = sTmp.replace ('<p>',monthName2[m]);
		sTmp = sTmp.replace ('yyyy',y);
		return sTmp.replace ('yy',padZero(y%100));
	}

	function closeCalendar() {
		hideCalendar();
		ctlToPlaceValue.value = constructDate(dateSelected,monthSelected,yearSelected);
	}

	/*** Month Pulldown	***/
	function StartDecMonth() {
		intervalID1 = setInterval("decMonth()",80);
	}

	function StartIncMonth() {
		intervalID1 = setInterval("incMonth()",80);
	}

	function incMonth () {
		monthSelected++;
		if (monthSelected > 11) {
			monthSelected = 0;
			yearSelected++;
		}
		constructCalendar();
	}

	function decMonth () {
		monthSelected--;
		if (monthSelected < 0) {
			monthSelected = 11;
			yearSelected--;
		}
		constructCalendar();
	}

	function constructMonth() {
		popDownYear()
		if (!monthConstructed) {
			sHTML = "";
			for (i=0; i<12; i++) {
				sName = monthName[i];
				if (i == monthSelected){
					sName = '<b>' + sName + '</b>';
				}
				sHTML += '<tr><td id="m' + i + '" onmouseover="this.className=\'selectMouseOver\'" onmouseout="this.className=\'\'" style="cursor:pointer" onclick="monthConstructed=false;monthSelected=' + i + ';constructCalendar();popDownMonth();event.cancelBubble=true">&nbsp;' + sName + '&nbsp;</td></tr>';
			}
			
			document.getElementById('selectMonth').innerHTML = '<table class="selectMonthYear" width="70" cellspacing="0" onmouseover="clearTimeout(timeoutID1)" onmouseout="clearTimeout(timeoutID1);timeoutID1=setTimeout(\'popDownMonth()\',100);event.cancelBubble=true">' + sHTML + '</table>';

			monthConstructed = true;
		}
	}

	function popUpMonth() {
		if (visMonth == 1) {
			popDownMonth();
			visMonth--;
		} else {
			constructMonth();
			crossMonthObj.visibility = (dom||ie) ? 'visible' : 'show';
			crossMonthObj.left = (parseInt(crossobj.left) + 50) + "px";
			crossMonthObj.top = (parseInt(crossobj.top) + 26) + "px";
			hideElement('SELECT', document.getElementById('selectMonth'));
			hideElement('APPLET', document.getElementById('selectMonth'));
			visMonth++;
		}
	}

	function popDownMonth() {
		crossMonthObj.visibility = 'hidden';
		visMonth = 0;
	}

	/*** Year Pulldown ***/
	function incYear() {
		for	(i=0; i<7; i++) {
			newYear	= (i + nStartingYear) + 1;
			if (newYear == yearSelected)
				txtYear = '<span class="CALYearSelected">&nbsp;' + newYear + '&nbsp;</span>';
			else
				txtYear = '<span class="CALYear">&nbsp;' + newYear + '&nbsp;</span>';
			document.getElementById('y'+i).innerHTML = txtYear;
		}
		nStartingYear++;
		bShow=true;
	}

	function decYear() {
		for	(i=0; i<7; i++) {
			newYear = (i + nStartingYear) - 1;
			if (newYear == yearSelected)
				txtYear = '<span class="CALYearSelected">&nbsp;' + newYear + '&nbsp;</span>';
			else
				txtYear = '<span class="CALYear">&nbsp;' + newYear + '&nbsp;</span>';
			document.getElementById('y'+i).innerHTML = txtYear;
		}
		nStartingYear--;
		bShow=true;
	}

	function selectYear(nYear) {
		yearSelected = parseInt(nYear + nStartingYear);
		yearConstructed = false;
		constructCalendar();
		popDownYear();
	}

	function constructYear() {
		popDownMonth();
		sHTML = '';
		if (!yearConstructed) {
			sHTML = '<tr><td align="center" onmouseover="this.className=\'selectMouseOver\'" onmouseout="clearInterval(intervalID1);this.className=\'\'" style="cursor:pointer" onmousedown="clearInterval(intervalID1);intervalID1=setInterval(\'decYear()\',30)" onmouseup="clearInterval(intervalID1)">-</td></tr>';

			j = 0;
			nStartingYear =	yearSelected - 3;
			for ( i = (yearSelected-3); i <= (yearSelected+3); i++ ) {
				sName = i;
				if (i == yearSelected) sName = '<b>' + sName + '</b>';
				sHTML += '<tr><td id="y' + j + '" onmouseover="this.className=\'selectMouseOver\'" onmouseout="this.className=\'\'" style="cursor:pointer" onclick="selectYear('+j+');event.cancelBubble=true">&nbsp;' + sName + '&nbsp;</td></tr>';
				j++;
			}

			sHTML += '<tr><td align="center" onmouseover="this.className=\'selectMouseOver\'" onmouseout="clearInterval(intervalID2);this.className=\'\'" style="cursor:pointer" onmousedown="clearInterval(intervalID2);intervalID2=setInterval(\'incYear()\',30)" onmouseup="clearInterval(intervalID2)">+</td></tr>';

			document.getElementById('selectYear').innerHTML = '<table class="selectMonthYear" width="44" cellspacing="0" onmouseover="clearTimeout(timeoutID2)" onmouseout="clearTimeout(timeoutID2);timeoutID2=setTimeout(\'popDownYear()\',100)">' + sHTML + '</table>';

			yearConstructed = true;
		}
	}

	function popDownYear() {
		clearInterval(intervalID1);
		clearTimeout(timeoutID1);
		clearInterval(intervalID2);
		clearTimeout(timeoutID2);
		crossYearObj.visibility= 'hidden';
		visYear = 0;
	}

	function popUpYear() {
		var leftOffset
		if (visYear==1) {
			popDownYear();
			visYear--;
		} else {
			constructYear();
			crossYearObj.visibility	= (dom||ie) ? 'visible' : 'show';
			leftOffset = parseInt(crossobj.left) + document.getElementById('spanYear').offsetLeft;
			//if (ie)
				leftOffset += 6;
			crossYearObj.left = leftOffset + "px";
			crossYearObj.top = (parseInt(crossobj.top) + 26) + "px";
			visYear++;
		}
	}

	/*** calendar ***/
	function WeekNbr(n) {
		// Algorithm used:
		// From Klaus Tondering's Calendar document (The Authority/Guru)
		// http://www.tondering.dk/claus/calendar.html
		// a = (14-month) / 12
		// y = year + 4800 - a
		// m = month + 12a - 3
		// J = day + (153m + 2) / 5 + 365y + y / 4 - y / 100 + y / 400 - 32045
		// d4 = (J + 31741 - (J mod 7)) mod 146097 mod 36524 mod 1461
		// L = d4 / 1460
		// d1 = ((d4 - L) mod 365) + L
		// WeekNumber = d1 / 7 + 1

		year = n.getFullYear();
		month = n.getMonth() + 1;
		if (startAt == 0) {
			day = n.getDate() + 1;
		} else {
			day = n.getDate();
		}

		a = Math.floor((14-month) / 12);
		y = year + 4800 - a;
		m = month + 12 * a - 3;
		b = Math.floor(y/4) - Math.floor(y/100) + Math.floor(y/400);
		J = day + Math.floor((153 * m + 2) / 5) + 365 * y + b - 32045;
		d4 = (((J + 31741 - (J % 7)) % 146097) % 36524) % 1461;
		L = Math.floor(d4 / 1460);
		d1 = ((d4 - L) % 365) + L;
		week = Math.floor(d1/7) + 1;

		return week;
	}

	function constructCalendar () {
		var aNumDays = Array (31,0,31,30,31,30,31,31,30,31,30,31);
		var dateMessage;
		var startDate = new Date (yearSelected,monthSelected,1);
		var endDate;

		constructCaption();

		if (monthSelected==1) {
			endDate = new Date (yearSelected,monthSelected+1,1);
			endDate = new Date (endDate - (24*60*60*1000));
			numDaysInMonth = endDate.getDate();
		} else {
			numDaysInMonth = aNumDays[monthSelected];
		}

		datePointer = 0;
		dayPointer = startDate.getDay() - startAt;
		
		if (dayPointer<0) dayPointer = 6;

		sHTML = '<table border="0"><tr>';

		if (showWeekNumber == 1) {
			sHTML += '<td width="27"><b>' + weekString + '</b></td><td class="CALweekseparator" width="1" rowspan="7"><img src="'+imgDir+'divider.gif" width="1" /></td>';
		}

		for (i = 0; i<7; i++) {
			sHTML += '<td width="27" align="right"><b><span class="CALDayNames">' + dayName[i] + '</span></b></td>';
		}

		sHTML += '</tr><tr>';
		
		if (showWeekNumber == 1) {
			sHTML += '<td align="right"><span class="CALWeekNumbers">' + WeekNbr(startDate) + '</span>&nbsp;</td>';
		}

		for	( var i=1; i<=dayPointer;i++ ) {
			sHTML += '<td>&nbsp;</td>';
		}
	
		for	( datePointer=1; datePointer <= numDaysInMonth; datePointer++ ) {
			dayPointer++;
			sHTML += '<td align="right">';
			sStyle='';
			highlightSelectedDay='';
			if ((datePointer == odateSelected) && (monthSelected == omonthSelected) && (yearSelected == oyearSelected))
			{ highlightSelectedDay = " class=\"CALselectedDay\" " }

			sHint = '';
			for (k = 0;k < HolidaysCounter; k++) {
				if ((parseInt(Holidays[k].d) == datePointer)&&(parseInt(Holidays[k].m) == (monthSelected+1))) {
					if ((parseInt(Holidays[k].y)==0)||((parseInt(Holidays[k].y)==yearSelected)&&(parseInt(Holidays[k].y)!=0))) {
						sHint += sHint=="" ? Holidays[k].desc : "\n"+Holidays[k].desc;
					}
				}
			}

			sHint = sHint.replace('/\"/g', '&quot;');

			dateRawMessage = selectDateMessage.replace('[date]',constructDate(datePointer,monthSelected,yearSelected));
			dateMessage = 'onmousemove="window.status=\''+dateRawMessage+'\'" onmouseout="window.status=\'\'" title="'+dateRawMessage+'" ';


			//////////////////////////////////////////////
			//////////  Modifications PinoToy  //////////
			//////////////////////////////////////////////
			if (enablePast == 0 && ((yearSelected < yearNow) || (monthSelected < monthNow) && (yearSelected == yearNow) || (datePointer < dateNow) && (monthSelected == monthNow) && (yearSelected == yearNow))) {
				selDayAction = '';
				isPast = 1;
			} else {
				selDayAction = 'href="javascript:dateSelected=' + datePointer + ';closeCalendar();"';
				isPast = 0;
			}

			if ((datePointer == dateNow) && (monthSelected == monthNow) && (yearSelected == yearNow)) {	
				// today
				cssStyle = 'CALToday';
			} else if (dayPointer % 7 == (startAt * -1)+1) {
				if (isPast==1)
					//past sundays
					cssStyle = 'CALSundayPast';
				else
					//sundays
					cssStyle = 'CALSunday';
			} else if ((dayPointer % 7 == (startAt * -1)+7 && startAt==1) || (dayPointer % 7 == startAt && startAt==0)) {
				if (isPast==1)
					//past saturday
					cssStyle = 'CALSaturdayPast';
				else
					//saturday
					cssStyle = 'CALSaturday';
			} else {
				if (isPast==1)
					//past weekdays
					cssStyle = 'CALWeekdayPast';
				else 
					//weekdays
					cssStyle = 'CALWeekday';
			}
			sHTML += "<a "+dateMessage+" title=\""+sHint+"\" "+selDayAction+" "+ highlightSelectedDay +">&nbsp;<span class=\""+cssStyle+"\">" + datePointer + "</span>&nbsp;</a></b>";

			sHTML += '';
			if ((dayPointer+startAt) % 7 == startAt) {
				sHTML += '</tr><tr>';
				if ((showWeekNumber == 1) && (datePointer < numDaysInMonth)) {
					sHTML += '<td align="right"><span class="CALWeekNumbers">' + (WeekNbr(new Date(yearSelected,monthSelected,datePointer+1))) + '</span>&nbsp;</td>';
				}
			}
		}

		document.getElementById('CALContent').innerHTML   = sHTML
		document.getElementById('spanMonth').innerHTML = '&nbsp;' +	monthName[monthSelected] + '&nbsp;<img id="changeMonth" src="'+imgDir+'drop1.png" width="12" height="10" border="0" alt="" />'
		document.getElementById('spanYear').innerHTML  = '&nbsp;' + yearSelected	+ '&nbsp;<img id="changeYear" src="'+imgDir+'drop1.png" width="12" height="10" border="0" alt="" />';
	}

	function showCalendar(ctl, ctl2, format, past, fx, fy) {
		if (past != null) enablePast = past;
		else enablePast = 0;
		if (fx != null) fixedX = fx;
		else fixedX = -1;
		if (fy != null) fixedY = fy;
		else fixedY = -1;
		popUpCalendar(ctl, ctl2, format);
	}

	function popUpCalendar(ctl, ctl2, format) {
		var leftpos = 0;
		var toppos  = 0;

		if (bPageLoaded) {
			if (crossobj.visibility == 'hidden') {
				ctlToPlaceValue = ctl2;
				dateFormat = format;
				formatChar = ' ';
				aFormat = dateFormat.split(formatChar);
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

				tokensChanged = 0;
				if (formatChar != "") {
					aData =	ctl2.value.split(formatChar);			// use user's date

					for (i=0; i<3; i++) {
						if ((aFormat[i] == "d") || (aFormat[i] == "dd")) {
							dateSelected = parseInt(aData[i], 10);
							tokensChanged++;
						} else if ((aFormat[i] == "m") || (aFormat[i] == "mm")) {
							monthSelected = parseInt(aData[i], 10) - 1;
							tokensChanged++;
						} else if (aFormat[i] == "yyyy") {
							yearSelected = parseInt(aData[i], 10);
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

				if ((tokensChanged != 3) || isNaN(dateSelected) || isNaN(monthSelected) || isNaN(yearSelected)) {
					dateSelected  = dateNow;
					monthSelected = monthNow;
					yearSelected  = yearNow;
				}

				odateSelected  = dateSelected;
				omonthSelected = monthSelected;
				oyearSelected  = yearSelected;

				aTag = ctl;
				do {
					aTag     = aTag.offsetParent;
					leftpos += aTag.offsetLeft;
					toppos  += aTag.offsetTop;
				} while (aTag.tagName != 'BODY');

				crossobj.left = ((fixedX == -1) ? ctl.offsetLeft + leftpos : fixedX) + "px";
				crossobj.top = ((fixedY == -1) ? ctl.offsetTop + toppos + ctl.offsetHeight + 2 : fixedY) + "px";
				constructCalendar (1, monthSelected, yearSelected);
				crossobj.visibility = (dom||ie) ? "visible" : "show";

				hideElement('SELECT', document.getElementById('calendar'));
				hideElement('APPLET', document.getElementById('calendar'));			

				bShow = true;
			} else {
				hideCalendar();
				if (ctlNow!=ctl) popUpCalendar(ctl, ctl2, format);
			}
			ctlNow = ctl;
		}
	}

	document.onkeypress = function hidecal1 (event) {
		var keyPressed;
		try {
			if (event.keyCode) {
				keyPressed = event.keyCode;
			} else {
				keyPressed = event.which;
			}
		} catch (ex) {
			keyPressed = window.event.keyCode;
		}

		if (keyPressed == 27) hideCalendar();
	}
	
	document.onclick = function hidecal2 () {
		if (!bShow) hideCalendar();
		bShow = false;
	}