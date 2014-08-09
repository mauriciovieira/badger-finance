<h1>$pageHeading</h1>
<form action="" method="post" enctype="multipart/form-data" name="Import" id="Import" accept-charset="ISO-8859-1" onSubmit="return validateCompleteForm(this, 'error');">

<fieldset style="width: 53em; height: 13em">
	<legend>$legend</legend>
    <table border="0" cellpadding="5" cellspacing="5"> 
            <tr> 
                 <td>$fileLabel</td> 
                 <td>$fileField</td> 
            </tr>
			<tr>
				<td>$accountSelectLabel</td>
				<td>$accountSelectFile</td>
			</tr>
			<tr>
				<td>$selectParserLabel</td>
				<td>$selectParserFile</td>
			</tr>
			<tr>
				<td></td>
				<td>$uploadButton</td>
			</tr>
	</table>
</fieldset>
<script type="text/javascript">
	$accountParserJS
</script>
</form>