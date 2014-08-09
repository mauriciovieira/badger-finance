<h1>$pageHeading</h1>
<form method="post" name="mainform" id="mainform" accept-charset="ISO-8859-1" action="$FormAction?action=save&amp;accountID=$AccountID&amp;backTo=$backTo">
<table>
  <tr>
    <td>$AccountLabel</td>
    <td>$hiddenAccID</td>
  </tr>
  <tr>
    <td>$titleLabel</td>
    <td>$titleField</td>
  </tr>
  <tr>
    <td>$descriptionLabel</td>
    <td>$descriptionField</td>
  </tr>
  <tr>
    <td>$beginDateLabel</td>
    <td>$beginDateField</td>
  </tr>  
  <tr>
    <td>$endDateLabel</td>
    <td>$endDateField</td>
  </tr>  
  <tr>
    <td>$amountLabel</td>
    <td>$amountField</td>
  </tr>
   <tr>
    <td>$outsideCapitalLabel</td>
    <td>$outsideCapitalField $outsideToolTip</td>
  </tr>
  <tr>
    <td>$transactionPartnerLabel</td>
    <td>$transactionPartnerField</td>
  </tr>
  <tr>
    <td>$categoryLabel</td>
    <td>$categoryField</td>
  </tr>
  <tr>
    <td>$repeatUnitLabel</td>
    <td>$everyLabel $repeatFrequencyField. $repeatUnitField</td>
  </tr>
  <tr>
  	<td>$transferalLabel</td>
  	<td>$transferalField</td>
  </tr>
  <tr id="transferalAccountRow" $transferalDataStyle>
  	<td>$transferalAccountLabel</td>
  	<td>$transferalAccountField</td>
  </tr>
  <tr id="transferalAmountRow" $transferalDataStyle>
  	<td>$transferalAmountLabel</td>
  	<td>$transferalAmountField</td>
  </tr>
  <tr>
  	<td>$rangeLabel</td>
  	<td>$rangeAllField $rangeAllLabel $rangeThisField $rangeThisLabel $rangePreviousField $rangePreviousLabel $rangeFollowingField $rangeFollowingLabel $rangeUnit</td>
  </tr>
  <tr>
  	<td></td>
  	<td style="display: none; color: red; font-weight: bold" id="categoryExpenseWarning">
  		$categoryExpenseWarning
  	</td>
  </tr>
  <tr>
    <td>$backBtn</td>
    <td>$submitBtn $deleteBtn</td>
  </tr>
</table>
$hiddenID
$hiddenType
$hiddenFinishedTransactionID
$backToIdField
$categoryExpenseJS
</form>