<h1>$askInsertTitle</h1>
<form name="agreeform" onSubmit="return defaultagree(this)" action="$askInsertAction" enctype="multipart/form-data" accept-charset="ISO-8859-1" method="POST">
<fieldset>
	<legend>$legend</legend>
	<p style="color:red; background-color: white; padding: 0.3em; border: 2px solid red; text-align: center;">$askImportWarning</p>
	<p>$askImportFileUploadLabel $askImportFileUpload</p>
	<p>$askImportVersionInfo<br />$askImportCurrentVersionInfo $versionInfo</p>
	<p>
		$confirmUploadField
		<b>$confirmUploadLabel</b>
	</p>
	<p>$askImportSubmit</p>
</form>

<script>
document.forms.agreeform.confirmUpload.checked=false
</script>

</fieldset>