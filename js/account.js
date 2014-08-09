function validateUpperLimit(id) {
/** -> will be implemented in next release
	-> problem: convert text to number. different user settings for number formating with , and .
	var returnValue;
	var lowerLimit = $("lowerLimit").value;
	var upperLimit = $(id).value;
	
	if((upperLimit!="" &&  upperLimit < lowerLimit) | !(/^[-0-9., ]+$/).test(upperLimit)) {
		alert("1:" + (/^[-0-9., ]+$/).test(upperLimit));
		if((/^[-0-9., ]+$/).test(upperLimit)) { 
			labelLower = getFieldLabel("lowerLimit");
			labelUpper = getFieldLabel(id);
			alert(labelLower +" > "+ labelUpper + ": " + lowerLimit +" > "+upperLimit);
		}
		return false;
	} else {
		return true;
	}
	
*/
}

function getFieldLabel(id) {
	var strFieldName;

	label = $("label" + id);
	if(label.textContent) strFieldName = label.textContent; //FF
	if(label.text) strFieldName = label.text; //IE
	if(label.innerText) strFieldName = label.innerText; //Opera
		
	strFieldName = strFieldName.replace( ":", "" );
	
	return strFieldName;
}