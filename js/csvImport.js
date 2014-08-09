function updateParser() {
	var currentAccountId = $F("accountSelect");
	
	if (currentAccountId && accountParsers[currentAccountId.toString()]) {
		var parserField = $("parserSelect");
		parserField.value = accountParsers[currentAccountId];
	}
}

function updateDisabledFields(transactionNumber) {
	var matchingTransaction = $F("matchingTransactionSelect" + transactionNumber);
	
	var categorySelect = $("categorySelect" + transactionNumber);
	var periodical = $("periodical" + transactionNumber);
	var exceptional = $("exceptional" + transactionNumber);
	var outside = $("outside" + transactionNumber);
	var account =$("account2Select" + transactionNumber);

	if (matchingTransaction == 'none') {
		categorySelect.disabled = false;
		periodical.disabled = false;
		exceptional.disabled = false;
		outside.disabled = false;
		account.disabled = false;
	} else {
		categorySelect.disabled = true;
		periodical.disabled = true;
		exceptional.disabled = true;
		outside.disabled = true;
		account.disabled = true;
	}
}