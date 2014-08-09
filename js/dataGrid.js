/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://badger.berlios.org 
*
**/
/**
 * dataGrid.js
 * 
 * @author Sepp
 * @author togro82
 * @version $LastChangedRevision: 1189 $
 *
**/

pageSettings = new PageSettings();

DataGrid = Class.create();
DataGrid.SortOrder = Class.create();
DataGrid.Filter = Class.create();

DataGrid.prototype = {
	initialize: function(arrParameters) {
		//variables
		this.uniqueId = arrParameters.uniqueId;
		this.sourceXML = arrParameters.sourceXML;
		
		this.columnOrder = arrParameters.columnOrder;	
		this.headerSize = arrParameters.headerSize;
		this.cellAlign = arrParameters.cellAlign;
		
		this.noRowSelectedMsg = arrParameters.noRowSelectedMsg;
		this.deleteMsg = arrParameters.deleteMsg;
		this.deleteRefreshType = arrParameters.deleteRefreshType;
		this.deleteAction = arrParameters.deleteAction;
		this.editAction = arrParameters.editAction;
		this.newAction = arrParameters.newAction;
		this.tplPath = arrParameters.tplPath;
		this.discardSelectedRows = arrParameters.discardSelectedRows;
		this.loadingMessage = arrParameters.loadingMessage;		
	
		this.mouseEventsDisabled = false;
		
		this.objRowActive;
		this.arrSelectedRows = new Array();	
		
		//initialize Sort and Filter Parameter
		this.sortOrder = new DataGrid.SortOrder(this);		
		this.filter = new DataGrid.Filter(this);
		this.filter.initFilterFields();
		
		this.loadData();
		
		//when we save selected rows at page refresh, then we should restore it here
		if (!this.discardSelectedRows) {
			this.restoreSelectedRows();
		}		
	},
	
	// retrieve data from server, define callback-function
	loadData: function() {
		// load data
		if (this.myAjaxLoad) {
			this.myAjaxLoad.transport.abort();
		};
		this.myAjaxLoad = new Ajax.Request(
			this.sourceXML, {
				method: 'post',
				parameters: "&sf=" + this.columnOrder + "&" +this.sortOrder.toQueryString() + "&" +this.filter.toQueryString(),
				onComplete: this.insertData.bind(this),
				onFailure: this.handleError.bind(this)
			}); 
		
		// show loading message, image, hide old data
		this.showMessageLayer('<span class="dgMessageHint"> '+this.loadingMessage+' </span>');
		$('dgDivScroll'+this.uniqueId).className = "dgDivScrollLoading";
		$('dgTableData'+this.uniqueId).style.visibility = "hidden"; 

		// filter image in footer
		if( this.filter.getNumberOfActiveFilters() >0 ) {
			$('dgFilterStatus'+this.uniqueId).style.visibility = "visible"; //filter active
		} else {
			$('dgFilterStatus'+this.uniqueId).style.visibility = "hidden"; //filter inactive
		}	
	},

	//XHR Error
	handleError: function() {
		this.showMessageLayer('<span class="dgMessageError"> XHR Error </span>');
	},
	
	// fill the datagrid with values
	insertData: function(objXHR) {
		var objXmlDoc = objXHR.responseXML;
		
		if(objXmlDoc) {			
			var xmlColumns = objXmlDoc.getElementsByTagName("column");
			var xmlRows = objXmlDoc.getElementsByTagName("row");

			//delete old table body if exists
			if($("dgTableData"+this.uniqueId).getElementsByTagName("tbody")[0]) {
				Element.remove($("dgTableData"+this.uniqueId).getElementsByTagName("tbody")[0])	
			}
			//create new table body
			var tableDataBody = document.createElement("tbody");
			$("dgTableData"+this.uniqueId).appendChild(tableDataBody);
			
			//column assignment
			//e.g. columnPosition['title'] is the first column in the xml-file;
			var columnPosition = new Array();
			//alert("xmlColumns.length: " + xmlColumns.length);
			for (intPosition=0; intPosition<xmlColumns.length; intPosition++) {
				if(xmlColumns[intPosition].textContent) columnName = xmlColumns[intPosition].textContent; //FF
				if(xmlColumns[intPosition].text) columnName = xmlColumns[intPosition].text; //IE
				if(xmlColumns[intPosition].innerHTML) columnName = xmlColumns[intPosition].innerHTML; //Opera
				columnPosition[columnName] = intPosition;		
			}
			
			//alert("xmlRows.length: " + xmlRows.length);
			for (j=0; j<xmlRows.length; j++) {
				//alert(j + "/"+ xmlRows.length);			
				var xmlCells = xmlRows[j].getElementsByTagName("cell");				
				
				//first cell of a row, is always a unique ID
				if(xmlCells[0].textContent) rowID =	URLDecode(xmlCells[0].textContent); //FF
				if(xmlCells[0].text) rowID = URLDecode(xmlCells[0].text); //IE
				if(xmlCells[0].innerHTML) rowID = URLDecode(xmlCells[0].innerHTML); //Opera
				
				// add separator
				if (xmlCells[0].getAttribute("marker")) {
					this.addSeparatorRow(this, tableDataBody);
				}
				
				//define a new row
				var newRow = document.createElement("tr");
				newRow.className = "dgRow";
				newRow.id = this.uniqueId+rowID;
				newRow.rowId = rowID;
				
				//add checkbox as the first cell
				var firstCell = document.createElement("td");
				firstCell.style.width = "25px";
				checkBox = document.createElement("input");
				checkBox.id = "check"+this.uniqueId+rowID;
				checkBox.name = "check"+this.uniqueId+rowID;
				checkBox.type = "checkbox";
				firstCell.appendChild(checkBox);
				firstCell.innerHTML = firstCell.innerHTML + "&nbsp;";
				newRow.appendChild(firstCell);

				//insert cell values
				// dgColumnOrder[0] -> 'balance' : name of the column
				// columnPosition['balance'] -> '1' : first column
				// cells[1].text{Content} -> '899.23' : value				
				for (i=0; i<this.columnOrder.length; i++) {
					var cell = document.createElement("td");
					cell.style.width = this.headerSize[i] + "px";
					cell.align = this.cellAlign[i];

					var xmlElement = xmlCells[columnPosition[this.columnOrder[i]]];
					// get cell className
					cell.className = xmlElement.getAttribute("class");
										
					// get cell inner content
					if (xmlElement.textContent) cell.innerHTML = xmlElement.textContent; // FF
					if (xmlElement.text) cell.innerHTML = xmlElement.text; //IE
					if (xmlElement.innerHTML) cell.innerHTML = xmlElement.innerHTML; //Opera
					// add image
					if (xmlElement.getAttribute("img")) {
						cell.innerHTML = "<img src='"+badgerRoot+"/"+xmlElement.getAttribute("img")+"' title='"+xmlElement.getAttribute("title")+"' />&nbsp;";
					}
					// decode content
					cell.innerHTML = URLDecode(cell.innerHTML) + "&nbsp;";				
					// add cell
					newRow.appendChild(cell);			
				}	
				//insert empty cell as last one (only display purposes)
				var lastCell = document.createElement("td");
				newRow.appendChild(lastCell);
				lastCell.innerHTML = "&nbsp;"; //filling dummy cell
					
				//add complete row to the grid
				tableDataBody.appendChild(newRow);
			}
			//refresh JS-behaviours of the rows
			Behaviour.apply();

			//activate previous selected rows and clean up array (remove filtered ids)
			var arrSelectedRowsCleaned = new Array();
			for (i=0; i<this.arrSelectedRows.length; i++) {
				if($(this.uniqueId + this.arrSelectedRows[i])) {
					arrSelectedRowsCleaned.push(this.arrSelectedRows[i]);
					$(this.uniqueId + this.arrSelectedRows[i]).className = "dgRowSelected";
					$("check"+$(this.uniqueId + this.arrSelectedRows[i]).id).checked = "checked";
				}
			}
			this.arrSelectedRows = arrSelectedRowsCleaned;
			
			// refresh row count
			$("dgCountTotal"+this.uniqueId).innerHTML = xmlRows.length;
			$("dgCount"+this.uniqueId).innerHTML = this.arrSelectedRows.length;
			
			// hide loading message
			this.hideMessageLayer();
			
			// display processed data
			$('dgTableData'+this.uniqueId).style.visibility = "visible";
		} else { //if(objXmlDoc)
			$("dgCount"+this.uniqueId).innerHTML = "0";
			this.showMessageLayer('<span class="dgMessageError"> '+objXHR.responseText+' </span>');
			//this.filter.reset();
		} // if(objXmlDoc)

		// hide loading image
		$('dgDivScroll'+this.uniqueId).className = "dgDivScroll";		
	},
	
	addSeparatorRow: function(dataGrid, tableDataBody) {
		var newRow = document.createElement("tr");
		newRow.id = dataGrid.uniqueId+"separator";
		newRow.className = "dgRowSeparator";	
	
		var firstCell = document.createElement("td");
		firstCell.style.width = "25px";
		firstCell.style.height = "5px";
		newRow.appendChild(firstCell);

		for (i=0; i<dataGrid.columnOrder.length; i++) {
			var cell = document.createElement("td");
			cell.style.width = dataGrid.headerSize[i] + "px";
			cell.style.height = "5px"; //overwrite css style
			newRow.appendChild(cell);						
		}
		tableDataBody.appendChild(newRow);
		
		var lastCell = document.createElement("td");
		lastCell.style.height = "5px";
		newRow.appendChild(lastCell);
	},
	gotoToday: function () {
		var separatorRow = $(this.uniqueId+"separator");
		var numberOfOffsetRows = 4;
		
		if (separatorRow) {
			// only for scrolling
			var rowToFocusAbove = separatorRow;
			var rowToFocusBelow = separatorRow;
			for(i=0; i<numberOfOffsetRows;i++) {
				if(rowToFocusAbove.previousSibling) {
					rowToFocusAbove = rowToFocusAbove.previousSibling;
					if(rowToFocusAbove.firstChild.childNodes[0].tagName == "INPUT") rowToFocusAbove.firstChild.childNodes[0].focus();
				}
				if(rowToFocusBelow.nextSibling) {
					rowToFocusBelow = rowToFocusBelow.nextSibling;
					if(rowToFocusBelow.firstChild.childNodes[0].tagName == "INPUT") rowToFocusBelow.firstChild.childNodes[0].focus();
				}
			}
			//activate row after separator
			this.activateRow(separatorRow.previousSibling);
		} else {
			// focus last checkbox
			var tableDataBody = $("dgTableData"+this.uniqueId);
			var tableRows = tableDataBody.getElementsByTagName("tr");
			
			if (tableRows) {
				if (tableRows.length > 4) {
					var lastRow = tableRows[tableRows.length-1];
					this.activateRow(lastRow);
				}				
			}
		}
	},
	// Row Handling
	//Activation -> Highlight, when mouse over
	activateRow: function (objRow) {
		if (objRow.className == "dgRow") objRow.className = "dgRowActive";
		if (objRow.className == "dgRowSelected") objRow.className = "dgRowSelectedActive";
		this.objRowActive = objRow;
		if($("check"+objRow.id)) {
			$("check"+objRow.id).focus();
		}
	},
	deactivateRow: function (objRow) {
		if (objRow.className == "dgRowActive") objRow.className = "dgRow";
		if (objRow.className == "dgRowSelectedActive") objRow.className = "dgRowSelected";
	},
	//Selection -> enable checkbox
	selectRow: function (objRow, disableFocus) {
		var position = this.arrSelectedRows.indexOf(objRow.rowId);
		if(position==-1) { //not existing in array
			this.arrSelectedRows.push(objRow.rowId);			
			//save selected rows
			if (!this.discardSelectedRows) this.saveSelectedRows();
		}		
		$("dgCount"+this.uniqueId).innerHTML = this.arrSelectedRows.length;
		if (objRow.className == "dgRow") objRow.className = "dgRowSelected";
		if (objRow.className == "dgRowActive") objRow.className = "dgRowSelectedActive";
		$("check"+objRow.id).checked = "checked";
		if (!disableFocus) $("check"+objRow.id).focus();

	},
	deselectRow: function (objRow, disableFocus) {
		//remove row id from array
		var position = this.arrSelectedRows.indexOf(objRow.rowId);
		if(position>=0) {
			this.arrSelectedRows[position] = null;
			this.arrSelectedRows = this.arrSelectedRows.compact();
			
			//save selected rows
			if (!this.discardSelectedRows) this.saveSelectedRows();
		}
		$("dgCount"+this.uniqueId).innerHTML = this.arrSelectedRows.length;		
		if (objRow.className == "dgRowSelected") objRow.className = "dgRow";
		if (objRow.className == "dgRowSelectedActive") objRow.className = "dgRowActive";
		$("check"+objRow.id).checked = "";
		if (!disableFocus) $("check"+objRow.id).focus();
	},
	deselectAllRows: function () {
		var allCheckboxes = Form.getInputs("dgForm"+this.uniqueId, "checkbox");
		
		for (i=0; i<allCheckboxes.length; i++) {
			if(allCheckboxes[i].id!="dgSelector"+this.uniqueId) {
				this.deselectRow(allCheckboxes[i].parentNode.parentNode, true);
			}
		}		
	},
	
	
	enableMouseEvents: function () {
		this.mouseEventsDisabled = false;
	},	
	refreshPage: function  () {
		location.href = location.href;
	},

	//Key-Events of the Rows
	KeyEvents: function (event) {
		if (!event) event=window.event;

		//KEY_DOWN
		if (event.keyCode == Event.KEY_DOWN | event.keyCode == Event.KEY_TAB) {
			Event.stop(event);
			var dataGrid = this.obj;	
			if (!dataGrid) { //IE
				dataGrid = Event.element(event).parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.obj;
			}
			
			//when dataGrid scrolls down, disable mouse events
			dataGrid.mouseEventsDisabled = true;
			window.setTimeout("$('dataGrid"+dataGrid.uniqueId +"').obj.enableMouseEvents()", 10);
			
			if (dataGrid.objRowActive) {
				var objNextRow = dataGrid.objRowActive.nextSibling;
				if (objNextRow) {
					if(objNextRow.tagName!="TR") objNextRow = objNextRow.nextSibling; //only FF, difference in the DOM
					dataGrid.deactivateRow(dataGrid.objRowActive);
					dataGrid.activateRow(objNextRow);
				}
			} else {
				dataGrid.objRowActive = $("dgTableData"+dataGrid.uniqueId).getElementsByTagName("tr")[0];
				dataGrid.activateRow(dataGrid.objRowActive);
			}			
		}
		
		//KEY_UP
		if (event.keyCode == Event.KEY_UP) {
			Event.stop(event);
			var dataGrid = this.obj;			
			if (!dataGrid) { //IE
				dataGrid = Event.element(event).parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.obj;
			}
			
			//when dataGrid scrolls down, disable mouse events
			dataGrid.mouseEventsDisabled = true;
			window.setTimeout("$('dataGrid"+dataGrid.uniqueId +"').obj.enableMouseEvents()", 10);
			
			if (dataGrid.objRowActive) {
				var objNextRow = dataGrid.objRowActive.previousSibling;
				if (objNextRow) {
					if(objNextRow.tagName!="TR") objNextRow = objNextRow.previousSibling; //only FF, difference in the DOM
					if (objNextRow) {
						dataGrid.deactivateRow(dataGrid.objRowActive);
						dataGrid.activateRow(objNextRow);
					}
				}
			}
		}
		//KEY_RETURN
		if (event.keyCode == Event.KEY_RETURN) {
			var dataGrid = this.obj;
			if (dataGrid.tagName) { //IE
				dataGrid = Event.element(event).parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.obj;
			}
			
			dataGrid.callEditEvent();
		}
		//KEY_DELETE
		if (event.keyCode == Event.KEY_DELETE) {
			var dataGrid = this.obj;
			if (dataGrid.tagName) { //IE
				dataGrid = Event.element(event).parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.obj;
			}			
			dataGrid.callDeleteEvent();
		}
		//KEY_SPACE (only for opera 8.X)
		//if (event.keyCode == 32) {
		//	var dataGrid = this.obj;
		//	if(dataGrid.objRowActive.className=="dgRowSelected" || dataGrid.objRowActive.className=="dgRowSelectedActive") {
		//		dataGrid.deselectRow(dataGrid.objRowActive);
		//	} else {
		//		dataGrid.selectRow(dataGrid.objRowActive);
		//	}	
		//}
	},
	// call site to  edit record with ID in a special page
	callEditEvent: function (id) {
		if(this.editAction) {
			if(!id) id = this.getFirstId(); //if called by button, get first ID
			if(id) {
				document.location.href = this.editAction + id;
			} else {
				alert (this.noRowSelectedMsg);
			}
		}
	},
	
	// delete all selected rows
	//  - send a background delete request to the server
	callDeleteEvent: function () {
		if(this.deleteAction) {	
			if(this.arrSelectedRows.length > 0) { 	
				//asks use, if he is sure
				var choise = confirm(this.deleteMsg +"("+this.arrSelectedRows.length+")");
				if (choise) {
					// delete data in background
					this.deleteTheseRows(this.deleteAction + this.arrSelectedRows);			
				} //if (choise)
			} else {
				alert (this.noRowSelectedMsg);
			} //this.arrSelectedRows.length > 0
		} //if (dgDeleteAction)
	},

	// call site to add a new record
	callNewEvent: function (addParam) {
		if(!addParam) addParam = "";
		if(this.newAction) {
			document.location.href = this.newAction + "&" + addParam;
		}
	},
	// delete data
	deleteTheseRows: function(strUrl) {
		var myAjaxDelete = new Ajax.Request(
			strUrl, {
				method: 'get',
				onComplete: this.deleteTheseRowsCallback.bind(this),
				onFailure: this.handleError.bind(this)
				});
	},
	// displays the message from backend-object
	deleteTheseRowsCallback: function(objXHR) {
		if (objXHR.responseText=="") {
			switch (this.deleteRefreshType) {
			case 'refreshDataGrid': 
				//refresh whole dataGrid				
				this.loadData();
				break;
			case 'refreshPage':
				//refresh whole page
				
				//deselect rows, before page refresh
				for (i=0; i < this.arrSelectedRows.length; i++) {
					objRow = $(this.uniqueId + this.arrSelectedRows[i]);
					this.deselectRow(objRow);
				}
				var tmpThis = this;	
				window.setTimeout(function() {tmpThis.refreshPage();}, 10);
				break;
			default:
				// no refresh, delete rows in frontend
				var numberOfRows = $("dgCountTotal"+this.uniqueId).innerHTML;
				for (i=0; i < this.arrSelectedRows.length; i++) {
					objRow = $(this.uniqueId + this.arrSelectedRows[i]);
					this.deselectRow(objRow);
					Element.remove(objRow);										
					numberOfRows--;					
				}
				$("dgCountTotal"+this.uniqueId).innerHTML = numberOfRows;
			} //switch	
		} else {
			this.showMessageLayer('<span class="dgMessageError"> '+objXHR.responseText+' </span>');
		}
	},
	
	saveSelectedRows: function() {
		pageSettings.setSettingSer("DataGrid"+this.uniqueId, "arrSelectedRows", this.arrSelectedRows);
	},
	restoreSelectedRows: function() {
		eval("this.restoreSelectedRowsCallback(" + pageSettings.getSettingSync("DataGrid"+this.uniqueId, "arrSelectedRows") + ")");		
	},
	restoreSelectedRowsCallback: function (objResult) {
		if(objResult) {
			this.arrSelectedRows = objResult;
		}
	},
	
	// get all ids from selected rows -> array
	getAllSelectedIds: function () {
		return this.arrSelectedRows;
	},
	
	// get first ids from selected rows -> array
	getFirstId: function () {
		return this.arrSelectedRows.first();
	},

	//display a message in the dataGrid footer
	showMessageLayer: function (strMessage) {
		var divMessage = $("dgMessage"+this.uniqueId);
		divMessage.style.display = "inline";
		divMessage.innerHTML = strMessage;
	},
	hideMessageLayer: function() {
		var divMessage = $("dgMessage"+this.uniqueId);
		divMessage.style.display = "none";
	},
	
	//preselect an entry
	preselectId: function (id) {
		var row = $(this.uniqueId + id);
		
		if (row) {
			this.arrSelectedRows.push(id);
			this.selectRow(row);
		}
	},
	getFirstColumnName: function () {
		return this.columnOrder[0];		
	},

	//Mouse-Events
	behaviour:  {
		//Mouse-Events of the rows (selecting, activating)
		'tr.dgRow' : function(element){
			element.onmouseover = function(){
				var dataGrid = this.parentNode.parentNode.parentNode.parentNode.obj;
				if (!dataGrid.mouseEventsDisabled) {
					if(dataGrid.objRowActive) dataGrid.deactivateRow(dataGrid.objRowActive);
					dataGrid.activateRow(this);
				}
			}
			element.onmouseout = function(){
				var dataGrid = this.parentNode.parentNode.parentNode.parentNode.obj;
				if (!dataGrid.mouseEventsDisabled) dataGrid.deactivateRow(this);
			}
			element.onclick = function(){
				var dataGrid = this.parentNode.parentNode.parentNode.parentNode.obj;
				if(this.className=="dgRowSelected" || this.className=="dgRowSelectedActive") {
					dataGrid.deselectRow(this);
				} else {
					dataGrid.selectRow(this);
				}
			}
			element.ondblclick = function(){
				var dataGrid = this.parentNode.parentNode.parentNode.parentNode.obj;
				//IE fires in dblclick also the click event
				//it is deselecting a selected row -> we select it again
				dataGrid.selectRow(this);
				dataGrid.callEditEvent(this.rowId);
			}
		},
		//Mouse-Events of the columns (sorting)
		'td.dgColumn' : function(element){
			element.onclick = function(){
				var dataGrid = this.parentNode.parentNode.parentNode.parentNode.obj;
				id = this.id.replace("dgColumn"+dataGrid.uniqueId,"");
				dataGrid.sortOrder.addNewSortOrder(id);
				dataGrid.loadData();
			}
		},
		
		// checkbox in the dataGrid-Header, for (de-)selecting all
		'input.dgSelector' : function(element){
			element.onclick = function(){
				var dataGrid = this.parentNode.parentNode.parentNode.parentNode.parentNode.obj;				
				var allCheckboxes = Form.getInputs("dgForm"+dataGrid.uniqueId,"checkbox");
				var tmpDiscardSelectedRows = dataGrid.discardSelectedRows;
				
				// disable setting save on each selection
				dataGrid.discardSelectedRows = true;
			
				if($F(this)=="on") {
					dataGrid.arrSelectedRows = new Array();
					//select all checkboxes		
					for (i=0; i<allCheckboxes.length; i++) {
						if(allCheckboxes[i].id!="dgSelector"+dataGrid.uniqueId) {
							dataGrid.selectRow(allCheckboxes[i].parentNode.parentNode, true);
						}
					}
				} else {
					//deselect all checkboxes	
					for (i=0; i<allCheckboxes.length; i++) {
						if(allCheckboxes[i].id!="dgSelector"+dataGrid.uniqueId) {
							dataGrid.deselectRow(allCheckboxes[i].parentNode.parentNode, true);
						}
					}
				} //if($F(this)=="on")
				
				//reactivate settings saving & save settings once
				dataGrid.discardSelectedRows = tmpDiscardSelectedRows;
				if(!tmpDiscardSelectedRows) {
					dataGrid.saveSelectedRows();
				}				
				
				this.focus();
			} //element.onclick 
		}
	}
}

DataGrid.SortOrder.prototype= {
	/*
	 * ok[0-2]: order key
	 * od[0-2]: order direction
	 * a: ascending
	 * d: descending
	 */
	sortOrder: new Object(),
	parent: new Object(),
	
	initialize: function(objDataGrid) {
		// remember handle to data grid object
		this.parent = objDataGrid;	
		
		// load data from page settings
		this.load();
		
		// initialise sort order
		if(this.sortOrder && this.sortOrder.ok0 && this.sortOrder.ok0!="") {
			// set sort order
			this.addNewSortOrder( this.sortOrder.ok0, this.sortOrder.od0);
		} else {
			// delete object
			this.sortOrder = new Object();
			// set default sorting, first column ascending
			this.addNewSortOrder( this.parent.getFirstColumnName(), "a" );
		}
		
		//return sort object
		return this;
	},
	toQueryString: function() {		
		var cleanedSortOrder = new Object();
		
		//clean up object, remove undefined attributes
		for (i in this.sortOrder) {
			if ( this.sortOrder[i] != undefined && i != "toJSONString") {
				cleanedSortOrder[i] = this.sortOrder[i];
			}
		}
		//Object to QueryString
		return $H(cleanedSortOrder).toQueryString();

	},
	
	addNewSortOrder: function(sortColumn, sortDirection) {
		// reset old sorting image
		if(this.activeSortColumn) this.changeColumnSortImage(this.activeSortColumn, "empty");
			
		if(sortColumn==this.sortOrder.ok0 & sortDirection==undefined) {
			// click on the same column:  change sort direction
			// no directions is specified when called by column click
			if (this.sortOrder.od0=="a") {
				// asc -> desc
				this.sortOrder.od0="d";
				this.changeColumnSortImage(sortColumn, "d");
			} else {
				// desc -> asc
				this.sortOrder.od0="a";
				this.changeColumnSortImage(sortColumn, "a");
			}
		} else {
			// click on a different column
			// or initialisation of sorting
			this.sortOrder.ok2 = this.sortOrder.ok1;
			this.sortOrder.od2 = this.sortOrder.od1;
			this.sortOrder.ok1 = this.sortOrder.ok0;
			this.sortOrder.od1 = this.sortOrder.od0;
			this.sortOrder.ok0 = sortColumn;
			if(sortDirection!="d") {
				this.sortOrder.od0 = "a";
				this.changeColumnSortImage(sortColumn, "a");
			} else {
				this.sortOrder.od0 = "d";
				this.changeColumnSortImage(sortColumn, "d");
			}
		}
		this.activeSortColumn = sortColumn;
		this.save();
	},
	save: function() {
		pageSettings.setSettingSer("DataGrid"+this.parent.uniqueId, "SortOrder", this.sortOrder);
	},
	load: function() {
		eval("this.loadCallback(" + pageSettings.getSettingSync("DataGrid"+this.parent.uniqueId, "SortOrder") + ")");
	},
	loadCallback: function(objResult) {
		this.sortOrder = objResult;
	},
	
	//change the image for sorting direction
	changeColumnSortImage: function (columnId, newSortOrder) {
		switch(newSortOrder) {
			case 'empty':
				$("dgImg"+this.parent.uniqueId+columnId).src = this.parent.tplPath + "dropEmpty.gif";
				break;
			case 'a':
				$("dgImg"+this.parent.uniqueId+columnId).src = this.parent.tplPath + "dropDown.png";
				break;
			case 'd':
				$("dgImg"+this.parent.uniqueId+columnId).src = this.parent.tplPath + "dropUp.png";
				break;
		}	
	}	
}


DataGrid.Filter.prototype = {
	activeFilter: new Array(),
	parent: new Object(),
	
	initialize: function(objDataGrid) {
		// remember handle to data grid object
		this.parent = objDataGrid;	
		
		// load data from page settings
		this.load();
		
		//return filter object
		return this;
	},
	reset: function() {		
		this.activeFilter = new Array();
	},
	save: function(strFilterName) {
		if (strFilterName) {
			strFilterName += "Filter";
			//save a specific filter
			//???????
			pageSettings.setSettingSer("DataGrid"+this.parent.uniqueId, strFilterName, this.activeFilter);
		} else {
			//save last used filter for this grid
			pageSettings.setSettingSer("DataGrid"+this.parent.uniqueId, "FilterActive", this.activeFilter);
		}
	},
	
	load: function(strFilterName) {
		if (strFilterName) {
			strFilterName += "Filter";
			//load a specific filter
			eval("this.loadCallback(" + pageSettings.getSettingSync("DataGrid"+this.parent.uniqueId, strFilterName) + ")");
			
		} else {
			//load last used filter for this grid
			eval("this.loadCallback(" + pageSettings.getSettingSync("DataGrid"+this.parent.uniqueId, "FilterActive") + ")");
		}
		
	},
	loadCallback: function(objResult) {
		this.activeFilter = objResult;
	},
	
	addFilterCriteria: function(field, operator, value) {
		if (!this.activeFilter) {
			this.activeFilter = new Array();
		}

		this.activeFilter.push({
			"field" : field,
			"operator" : operator,
			"value" : value
		});
	},
	
	setFilterFields: function (arrayOfFields) {
		this.reset();
		
		if(arrayOfFields){
			for (i=0; i<arrayOfFields.length; i++) {
				if( $(arrayOfFields[i]) ) {
					if( $F(arrayOfFields[i]) != "" && $F(arrayOfFields[i])!="NULL" ) {
						strField = arrayOfFields[i];
						strValue = $F(arrayOfFields[i]);
						if( $(arrayOfFields[i]+"Filter") ) {
							strOperator = $F(arrayOfFields[i]+"Filter");
						} else {					
							strOperator = "eq";
						}
						if (strField == "categoryId" & strValue.substr(0, 1) == '-') {
							strField = "parentCategoryId";
							strValue = strValue * -1;
						}
						//alert(strField +":"+ strOperator +":"+ strValue);
						this.addFilterCriteria(strField, strOperator, strValue);
					}
				}		
			}
		}		
		this.save();
		this.parent.loadData();
	},
	resetFilterFields: function (arrayOfFields) {
		this.reset();		
		this.save();
		this.parent.loadData();
		
		//reset values in form fields
		if(arrayOfFields) {
			for (i=0; i<arrayOfFields.length; i++) {
				if( $(arrayOfFields[i]) ) {
					$(arrayOfFields[i]).value = "";
				}
			}
		}
	},
	initFilterFields: function () {
		if (this.activeFilter) {

			for (i=0; i<this.activeFilter.length; i++) {
				strField = this.activeFilter[i].field;
				strValue = this.activeFilter[i].value;
				if(strField=="parentCategoryId") {
					strField = "categoryId";
					strValue = strValue * -1;
				}
				$(strField).value = strValue;
				if ( $(strField+"Filter") ) {
					$(strField+"Filter").value = this.activeFilter[i].operator;
				}				
			}
		}
	},
	getNumberOfActiveFilters: function() {
		if (this.activeFilter) {
			return this.activeFilter.length;
		} else return 0;
	},
	toQueryString: function() {		
		var arrResult = new Array();
		
		//build querystring
		if(this.activeFilter) {
			for (i=0; i<this.activeFilter.length; i++) {
				if ( this.activeFilter[i] != undefined && i != "toJSONString") {
					arrResult["fk"+i] = this.activeFilter[i].field;
					arrResult["fo"+i] = this.activeFilter[i].operator;
					arrResult["fv"+i] = this.activeFilter[i].value;
				}
			}
			//Object to QueryString
			return $H(arrResult).toQueryString();
		} else return "";		
	}		
}

function URLDecode(strEncodeString) {
	// Create a regular expression to search all +s in the string
	var lsRegExp = /\+/g;
	// Return the decoded string	  
	return unescape(strEncodeString.replace(lsRegExp, " "));
}