function agreesubmit(){
	var val = $F('confirmUpload');
	
	var button = $('submit');
	
	button.disabled = !val;
}