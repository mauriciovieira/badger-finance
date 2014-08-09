<h1>$selectTitle</h1>

<form method="post" action="$selectFormAction" accept-charset="ISO-8859-1" id="selectForm" name="mainform">
		
	<fieldset style="position: absolute; left: 1em; top: 8em; width: 7em; height: 4em;">
		<legend>$graphTypeText</legend>
		$trendRadio $trendLabel<br />
		$categoryRadio $categoryLabel
	</fieldset>
	<fieldset style="position: absolute; left: 1em; top: 14em; width: 7em; height: 4em;">
		<legend>$categoryTypeText</legend>
		$inputRadio $inputLabel<br />
		$outputRadio $outputLabel
	</fieldset>
	<fieldset style="position: absolute; left: 10em; top: 8em; width: 22em; height: 10em;">
		<legend>$timeFrameText</legend>
			<p>$monthSelect $yearInput</p>
		<table>		
			<tr>
				<td>$fromText</td><td>$startDateField</td>
			</tr>
			<tr>
				<td>$toText</td><td>$endDateField</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="position: absolute; left: 1em; top: 20em; width: 31em; height: 4em;">
		<legend>$summarizeCategoriesText</legend>
		$summarizeRadio $summarizeLabel<br />
		$distinguishRadio $distinguishLabel
	</fieldset>
	
	$accountField
	$dateFormatField
	$errorMsgAccountMissingField
	$errorMsgStartBeforeEndField
	$errorMsgEndInFutureField
</form>
<fieldset style="position: absolute; left: 34em; top: 8em; width: 410px; height: 16em;">
	<legend>$accountsText</legend>
	$accountSelect
	<p>$differentCurrencyWarningText</p>
</fieldset>
<p style="margin-top: 19em;">$submitButton</p>

<div id="flashContainer" class="flashContainer"></div>
<div class="flashClear"></div>