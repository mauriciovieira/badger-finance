function showBadgerHelp() {
	chapter = "";
	try {
		chapter = badgerHelpChapter;
	} catch(ex) {}

	var helpWindow = window.open(badgerHelpRoot + "/help_" + badgerHelpLang + ".html#" + chapter, "badgerHelp");
	helpWindow.focus();
}

function showReleaseNotes() {
	var rnWindow = window.open(badgerHelpRoot + "/releaseNotes.html", "badgerHelp");
	rnWindow.focus();
}