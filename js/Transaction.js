function checkBeginEndDate() {
	var beginDate = $("beginDate");
	var endDate =$("endDate");

	var val = $F("range");
	
	if (val == "all") {
		beginDate.disabled = false;
		endDate.disabled = false;
	} else {
		beginDate.disabled = true;
		endDate.disabled = true;
	}
}

function toggleTransferal() {
	var showTransferalData = $F("transferalEnabled");
	var displayType = showTransferalData ? "" : "none";
	
	$("transferalAccountRow").style.display = displayType;
	$("transferalAmountRow").style.display = displayType;
	//also hide input element, so that validation check will not be performed
	$("transferalAmount").style.display = displayType;
}

function updateTransferalAmount() {
	var currentAmount = $F("amount");
	var currentTransferalAmount = $F("transferalAmount");
	var negativeCurrentAmount;
	
	if (currentAmount.replace(/ /g, "").substr(0, 1) == "-") {
		negativeCurrentAmount = currentAmount.replace(/^ *-/, "");
	} else {
		negativeCurrentAmount = "-" + currentAmount.replace(/^ */, "");
	}
	if (previousAmount == currentTransferalAmount) {
		$("transferalAmount").value = negativeCurrentAmount;
		adjustInputNumberClass($("transferalAmount"));
	}
	
	previousAmount = negativeCurrentAmount;
}

function adjustInputNumberClass(elm) {
	var val = elm.value;
	
	if (isNegative(val)) {
		elm.className = "inputNumberMinus";
	} else {
		elm.className = "inputNumber"
	}
	
	updateExpenseWarning();
}

function isNegative(val) {
	return val.replace(/ /g, "").substr(0, 1) == "-";
}

function updateExpenseWarning() {
	var val = $F("amount");
	var categoryId = $F("category");
	var expenseWarning = $("categoryExpenseWarning")
	if (
		categoryId
		&& categoryExpense[categoryId]
		&& !isNegative(val)
	) {
		expenseWarning.style.display = "table-cell";
	} else {
		expenseWarning.style.display = "none";
	}
}

function validateTitle(id) {	
	if ($(id).value=="" ) {
		var catId = $("category").options.selectedIndex;
		var catValue = $("category").options[catId].text;
		
		if( catValue ) {
			$(id).value = catValue;
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
	
}