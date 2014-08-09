function showTwistie(id) {
	$(id + "Closed").style.display = "none";
	$(id + "Opened").style.display = "block";
}

function hideTwistie(id) {
	$(id + "Closed").style.display = "block";
	$(id + "Opened").style.display = "none";
}
	