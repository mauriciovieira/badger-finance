<h1>$uploadTitle</h1>
<form action="" method="post" name="mainform" id="mainform">
 	<div id="CSVscroll">	
		<table cellpadding="2" cellspacing="0">
			<tr>
			<th>$tableHeadSelect $HeadSelectToolTip</th>
			<th>$tableHeadCategory<br />$HeadCategoryToolTip</th>
			<th>$tableHeadValutaDate $HeadValueDateToolTip</th>
	   		<th>$tableHeadTitle<br />$HeadTitleToolTip</th>
	   		<th>$tableHeadAmount<br />$HeadAmountToolTip</th>
	   		<th>$tableHeadTransactionPartner $HeadTransactionPartnerToolTip</th>
	   		<th>$tableHeadDescription $HeadDescriptionToolTip</th>
	   		<th>$tableHeadPeriodical $HeadPeriodicalToolTip</th>
	   		<th>$tableHeadExceptional $HeadExceptionalToolTip</th>
	   		<th>$tableHeadOutside $HeadOutsideToolTip</th>
	   		<th>$tableHeadAccount<br />$HeadAccountToolTip</th>
	   		<th>$tableHeadMatching<br />$HeadMatchingToolTip</th>
	   		</tr>
	   		$tplOutput
   		</table>
	</div>
	$hiddenField 
	$hiddenAccountId
	$buttonSubmit 
</form>