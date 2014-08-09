<h1>$pageHeading</h1>
<form name="UserSettings" method="post" accept-charset="ISO-8859-1" action="{BADGER_ROOT}/core/UserSettingsAdmin/UserSettingsAdmin.php" onSubmit="return validateStandard(this, 'error');">
	<fieldset style="width: 30em;">
		<legend>$FsHeading</legend>
		<table>
			<tr>
				<td>$TemplateLabel</td>
				<td style="text-align: right; white-space: nowrap;">$TemplateField</td>
			</tr>
			<tr>
				<td>$LanguageLabel</td>
				<td style="text-align: right; white-space: nowrap;">$LanguageField</td>
			<tr>
				<td>$DateFormatLabel</td>
				<td style="text-align: right; white-space: nowrap;">$DateFormatField</td>
			</tr>
			<tr>
				<td>$SeperatorsLabel</td>
				<td style="text-align: right; white-space: nowrap;">$SeperatorsField</td>
			</tr>
			<tr>
				<td>$MaxLoginLabel</td>
				<td style="text-align: right; white-space: nowrap;">$MaxLoginField</td>
			</tr>
			<tr>
				<td>$LockOutTimeLabel</td>
				<td style="text-align: right; white-space: nowrap;">$LockOutTimeField</td>
			</tr>
			<tr>
				<td>$StartPageLabel</td>
				<td style="text-align: right; white-space: nowrap;">$StartPageField</td>
			</tr>
			<tr>
				<td>$SessionTimeLabel</td>
				<td style="text-align: right; white-space: nowrap;">$SessionTimeField</td>
			</tr>
			<tr>
				<td>$futureCalcSpanLabel</td>
				<td style="text-align: right; white-space: nowrap;">$futureCalcSpanField</td>
			</tr>
			<tr>
				<td>$autoExpandPlannedTransactionsLabel</td>
				<td style="text-align: right; white-space: nowrap;">$autoExpandPlannedTransactionsField</td>
			</tr>
		</table>
	</fieldset><br/>
	<fieldset style="width: 30em;">
		<legend>$matchingHeading</legend>
		<table>
			<tr>
				<td>$matchingDateDeltaLabel</td>
				<td style="text-align: right; white-space: nowrap;">$matchingDateDeltaField</td>
			</tr>
			<tr>
				<td>$matchingAmountDeltaLabel</td>
				<td style="text-align: right; white-space: nowrap;">$matchingAmountDeltaField</td>
			</tr>
			<tr>
				<td>$matchingTextSimilarityLabel</td>
				<td style="text-align: right; white-space: nowrap;">$matchingTextSimilarityField</td>
			</tr>
		</table>
	</fieldset>
	<br/>
	<fieldset style="width: 30em;">
		<legend>$PWFormLabel</legend>
		<table>
			<tr>
				<td>$OldPwLabel</td>
				<td>$OldPwField</td>
			</tr>
			<tr>
				<td>$NewPwLabel</td>
				<td>$NewPwField</td>
			<tr>
				<td>$ConfPwLabel</td>
				<td>$ConfPwField</td>
			</tr>
		</table>
	</fieldset>
	<table style="clear: both;">
		<tr>
			<td style="width: 17em;"></td>
			<td>$btnSubmit</td>
		</tr>
	</table>
</form>
$Feedback