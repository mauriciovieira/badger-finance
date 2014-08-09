<?php
/*
* ____          _____   _____ ______ _____
*|  _ \   /\   |  __ \ / ____|  ____|  __ \
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  /
*| |_) / ____ \| |__| | |__| | |____| | \ \
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://badger.berlios.org
*
**/
define ('BADGER_ROOT', '../..');

require_once BADGER_ROOT . '/includes/fileHeaderFrontEnd.inc.php';
require_once BADGER_ROOT . '/modules/importExport/exportLogic.php';
require_once BADGER_ROOT . '/modules/account/Account.class.php';

if (isset($_GET['mode'])) {
	$action = getGPC($_GET, 'mode');
} else {
	$action = 'displayProcedure';
}

switch ($action) {
	case 'backupDatabase':
		backupDatabase();
		break;

	case 'update':
		update();
		break;

	case 'displayProcedure':
	default:
		displayProcedure();
		break;
}

function displayProcedure() {
	global $tpl;
	$widgets = new WidgetEngine($tpl);

	$widgets->addNavigationHead();

	$procedureTitle = getUpdateTranslation('updateProcedure', 'pageTitle');
	echo $tpl->getHeader($procedureTitle);

	$legend = getUpdateTranslation('updateProcedure', 'legend');
	$updateInformation = getUpdateTranslation('updateProcedure', 'updateInformation');
	$dbVersionText = getUpdateTranslation('updateProcedure', 'dbVersionText');
	$dbVersion = getBadgerDbVersion();
	$fileVersionText = getUpdateTranslation('updateProcedure', 'fileVersionText');
	$fileVersion = BADGER_VERSION;
	$stepDescription = getUpdateTranslation('updateProcedure', 'stepDescription');
	$step1PreLink = getUpdateTranslation('updateProcedure', 'step1PreLink');
	$step1LinkTarget = BADGER_ROOT . '/core/update/update.php?mode=backupDatabase';
	$step1LinkText = getUpdateTranslation('updateProcedure', 'step1LinkText');
	$step1PostLink = getUpdateTranslation('updateProcedure', 'step1PostLink');
	$step2PreLink = getUpdateTranslation('updateProcedure', 'step2PreLink');
	$step2LinkTarget = BADGER_ROOT . '/core/update/update.php?mode=update';
	$step2LinkText = getUpdateTranslation('updateProcedure', 'step2LinkText');
	$step2PostLink = getUpdateTranslation('updateProcedure', 'step2PostLink');

	eval('echo "' . $tpl->getTemplate('update/procedure') . '";');
	eval('echo "' . $tpl->getTemplate('badgerFooter') . '";');
}

function backupDatabase() {
	sendSqlDump();

	exit;
}

function update() {
	global $tpl, $us;

	$versionHistory = array (
		array (
			'version' => '1.0 beta',
			'function' => 'update1_0betaTo1_0beta2'
		),
		array (
			'version' => '1.0 beta 2',
			'function' => 'update1_0beta2To1_0beta3'
		),
		array (
			'version' => '1.0 beta 3',
			'function' => 'update1_0beta3To1_0'
		),
		array (
			'version' => '1.0',
			'function' => false
		)
	);

	$widgets = new WidgetEngine($tpl);

	$widgets->addNavigationHead();

	$updateTitle = getUpdateTranslation('updateUpdate', 'pageTitle');
	echo $tpl->getHeader($updateTitle);

	$currentDbVersion = getBadgerDbVersion();

	for ($dbVersionIndex = 0; $dbVersionIndex < count($versionHistory); $dbVersionIndex++) {
		if ($versionHistory[$dbVersionIndex]['version'] == $currentDbVersion) {
			break;
		}
	}

	$numNeededSteps = count($versionHistory) - $dbVersionIndex - 1;

	$dbVersion = $currentDbVersion;
	$fileVersion = BADGER_VERSION;

	$betweenVersions = '';
	for ($i = $dbVersionIndex + 1; $i < count($versionHistory) - 1; $i++) {
		$currentVersion = $versionHistory[$i];
		eval('$betweenVersions .= "' . $tpl->getTemplate('update/betweenVersionsLine') . '";');
	}

	$betweenVersionsText = getUpdateTranslation('updateUpdate', 'betweenVersionsText');

	if ($betweenVersions !== '') {
		eval('$betweenVersionsBlock = "' . $tpl->getTemplate('update/betweenVersionsBlock') . '";');
	} else {
		$betweenVersionsBlock = '';
	}

	$updateLog = '';

	$preCurrentText = getUpdateTranslation('updateUpdate', 'preCurrentText');
	$postCurrentText = getUpdateTranslation('updateUpdate', 'postCurrentText');
	$postNextText = getUpdateTranslation('updateUpdate', 'postNextText');

	$logEntryHeader = getUpdateTranslation('updateUpdate', 'logEntryHeader');

	for ($currentVersionIndex = $dbVersionIndex; $currentVersionIndex < count($versionHistory) - 1; $currentVersionIndex++) {
		$currentVersion = $versionHistory[$currentVersionIndex]['version'];
		$nextVersion = $versionHistory[$currentVersionIndex + 1]['version'];

		eval('$updateLog .= "' . $tpl->getTemplate('update/updateStepHeader') . '";');

		$logEntry = $versionHistory[$currentVersionIndex]['function']();

		eval('$updateLog .= "' . $tpl->getTemplate('update/updateStepEntry') . '";');
	}

	$updateInformation = getUpdateTranslation('updateUpdate', 'updateInformation');
	$errorInformation = getUpdateTranslation('updateUpdate', 'errorInformation');
	$dbVersionText = getUpdateTranslation('updateProcedure', 'dbVersionText');
	$fileVersionText = getUpdateTranslation('updateProcedure', 'fileVersionText');
	$updateFinished = getUpdateTranslation('updateUpdate', 'updateFinished');

	$goToStartPagePreLink = getUpdateTranslation('updateUpdate', 'goToStartPagePreLink');
	$goToStartPageLinkText = getUpdateTranslation('updateUpdate', 'goToStartPageLinkText');
	$goToStartPagePostLink = getUpdateTranslation('updateUpdate', 'goToStartPagePostLink');

	$startPageURL = BADGER_ROOT . '/' . $us->getProperty('badgerStartPage');

	eval('echo "' . $tpl->getTemplate('update/update') . '";');
	eval('echo "' . $tpl->getTemplate('badgerFooter') . '";');
}

function update1_0betaTo1_0beta2() {
	$log = '';

/*
	$log .= "&rarr; Deleting duplicate i18n entries.\n";
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'error_confirm_failed' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'error_empty_password' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'error_old_password_not_correct' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'new_password_name' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'old_password_description' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'old_password_name' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'password_change_commited' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'seperators_description' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'seperators_name' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'session_time_description' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'session_time_name' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'site_name' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'start_page_description' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'start_page_name' AND LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'submit_button' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'template_description' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'template_name' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'user_settings_change_commited' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'user_settings_heading' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'UserSettingsAdmin' AND `id` = 'login_button' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'Navigation' AND `id` = 'Help' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'forecast' AND `id` = 'plannedTransactionsLabel' LIMIT 1");
	$log .= doQuery("DELETE FROM `i18n` WHERE `page_id` = 'forecast' AND `id` = 'plannedTransactionsToolTip' LIMIT 1");
*/
	$log .= "&rarr; Adding primary key to i18n.\n";
	$log .= doQuery("ALTER IGNORE TABLE `i18n` ADD PRIMARY KEY ( `page_id` , `id` ( 255 ) )", array(-1));

	$log .= "&rarr; Adding primary key to langs.\n";
	$log .= doQuery("ALTER TABLE `langs` ADD PRIMARY KEY ( `id` )", array(-1));

	$log .= "&rarr; Removing old sessions.\n";
	$log .= doQuery("TRUNCATE TABLE session_master");
	$log .= "&rarr; Adding primary key to session_master.\n";
	$log .= doQuery("ALTER TABLE `session_master` ADD PRIMARY KEY ( `sid` )", array(-1));

	$log .= "&rarr; Removing old session data.\n";
	$log .= doQuery("TRUNCATE TABLE session_global");
	$log .= "&rarr; Adding primary key to session_global.\n";
	$log .= doQuery("ALTER TABLE `session_global` ADD PRIMARY KEY ( `sid` , `variable` );", array(-1));

	$log .= "&rarr; Creating references from transferred recurring transactions to recurring transactions.\n";
	$log .= doQuery("UPDATE `finished_transaction` f SET `planned_transaction_id` = (SELECT planned_transaction_id FROM planned_transaction p WHERE f.category_id <=> p.category_id AND f.account_id <=> p.account_id AND f.title <=> p.title AND f.transaction_partner <=> p.transaction_partner AND f.amount <=> p.amount LIMIT 1)");

	$log .= "&rarr; Creating new account id sequence table.\n";
	$log .= doQuery("CREATE TABLE IF NOT EXISTS `account_ids_seq` (
			`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$log .= "&rarr; Deleting old account id sequence.\n";
	$log .= doQuery("TRUNCATE TABLE account_ids_seq");
	$log .= "&rarr; Inserting max id to account sequence table.\n";
	$log .= doQuery("INSERT INTO account_ids_seq (id) VALUES ((SELECT MAX(account_id) FROM account) + 1)");

	$log .= "&rarr; Creating new category id sequence table.\n";
	$log .= doQuery("CREATE TABLE IF NOT EXISTS `category_ids_seq` (
			`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$log .= "&rarr; Deleting old category id sequence.\n";
	$log .= doQuery("TRUNCATE TABLE category_ids_seq");
	$log .= "&rarr; Inserting max id to category sequence table.\n";
	$log .= doQuery("INSERT INTO category_ids_seq (id) VALUES ((SELECT MAX(category_id) FROM category) + 1)");

	$log .= "&rarr; Creating new currency id sequence table.\n";
	$log .= doQuery("CREATE TABLE IF NOT EXISTS `currency_ids_seq` (
			`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$log .= "&rarr; Deleting old currency id sequence.\n";
	$log .= doQuery("TRUNCATE TABLE currency_ids_seq");
	$log .= "&rarr; Inserting max id to currency sequence table.\n";
	$log .= doQuery("INSERT INTO currency_ids_seq (id) VALUES ((SELECT MAX(currency_id) FROM currency) + 1)");

	$log .= "&rarr; Creating new finished transaction id sequence table.\n";
	$log .= doQuery("CREATE TABLE IF NOT EXISTS `finished_transaction_ids_seq` (
			`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$log .= "&rarr; Deleting old finished transaction id sequence.\n";
	$log .= doQuery("TRUNCATE TABLE finished_transaction_ids_seq");
	$log .= "&rarr; Inserting max id to finished transaction sequence table.\n";
	$log .= doQuery("INSERT INTO finished_transaction_ids_seq (id) VALUES ((SELECT MAX(finished_transaction_id) FROM finished_transaction) + 1)");

	$log .= "&rarr; Creating new navigation id sequence table.\n";
	$log .= doQuery("CREATE TABLE IF NOT EXISTS `navi_ids_seq` (
			`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$log .= "&rarr; Deleting old navigation id sequence.\n";
	$log .= doQuery("TRUNCATE TABLE navi_ids_seq");
	$log .= "&rarr; Inserting max id to navigation sequence table.\n";
	$log .= doQuery("INSERT INTO navi_ids_seq (id) VALUES ((SELECT MAX(navi_id) FROM navi) + 1)");

	$log .= "&rarr; Creating new planned transaction id sequence table.\n";
	$log .= doQuery("CREATE TABLE IF NOT EXISTS `planned_transaction_ids_seq` (
			`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$log .= "&rarr; Deleting old planned transaction id sequence.\n";
	$log .= doQuery("TRUNCATE TABLE planned_transaction_ids_seq");
	$log .= "&rarr; Inserting max id to planned transaction sequence table.\n";
	$log .= doQuery("INSERT INTO planned_transaction_ids_seq (id) VALUES ((SELECT MAX(planned_transaction_id) FROM planned_transaction) + 1)");

	$log .= "&rarr; Dropping old account id sequence table.\n";
	$log .= doQuery("DROP TABLE IF EXISTS accountids_seq");
	$log .= doQuery("DROP TABLE IF EXISTS accountIds_seq");

	$log .= "&rarr; Dropping old category id sequence table.\n";
	$log .= doQuery("DROP TABLE IF EXISTS categoryids_seq");
	$log .= doQuery("DROP TABLE IF EXISTS categoryIds_seq");

	$log .= "&rarr; Dropping old currency id sequence table.\n";
	$log .= doQuery("DROP TABLE IF EXISTS currencyids_seq");
	$log .= doQuery("DROP TABLE IF EXISTS currencyIds_seq");

	$log .= "&rarr; Dropping old finished transaction id sequence table.\n";
	$log .= doQuery("DROP TABLE IF EXISTS finishedtransactionids_seq");
	$log .= doQuery("DROP TABLE IF EXISTS finishedTransactionIds_seq");

	$log .= "&rarr; Dropping old navigation id sequence table.\n";
	$log .= doQuery("DROP TABLE IF EXISTS naviids_seq");
	$log .= doQuery("DROP TABLE IF EXISTS naviIds_seq");

	$log .= "&rarr; Dropping old planned transaction id sequence table.\n";
	$log .= doQuery("DROP TABLE IF EXISTS plannedtransactionids_seq");
	$log .= doQuery("DROP TABLE IF EXISTS plannedTransactionIds_seq");

	$log .= "&rarr; Dropping CSV parser table not used anymore.\n";
	$log .= doQuery("DROP TABLE IF EXISTS csv_parser\n");

	$log .= "&rarr; Adding new translation entries.\n";
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'futureCalcSpanLabel', en = 'Planning horizon (months)', de = 'Planungszeitraum in Monaten'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'futureCalcSpanDescription', en = 'Please enter how far into the future you would like to be able to plan. With usability in mind, recurring transactions will only be displayed as far into the future as you enter here. ', de = 'Geben Sie hier ein, wie weit Sie in die Zukunft planen m&ouml;chten. Wiedekehrende Transaktionen werden der &Uuml;bersichtlichkeit wegen nur so weit in die Zukunft dargestellt, wie Sie hier eingeben.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics', id = 'trendTotal', en = 'Total', de = 'Gesamt'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountAccount', id = 'pageTitlePropNew', en = 'New Account', de = 'Konto erstellen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'badger_login', id = 'sessionTimeout', en = 'Your session timed out. You have been logged out for security reasons.', de = 'Ihre Sitzung ist abgelaufen. Sie wurden aus Sicherheitsgr&uuml;nden ausgeloggt.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'step1PostLink', en = '', de = ''");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'step2PreLink', en = 'Please click the following link to start the database update.', de = 'Bitte klicken Sie auf folgenden Link, um die Datenbank-Aktualisierung zu beginnen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'step1PreLink', en = 'Please click the following link and save the file to your computer.', de = 'Bitte klicken Sie auf folgenden Link und speichern Sie die Datei auf Ihrem Computer.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'step1LinkText', en = 'Save backup', de = 'Sicherungskopie speichern'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'fileVersionText', en = 'File version:', de = 'Datei-Version:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'stepDescription', en = 'The update consists of two simple steps. First, a backup of the database is saved to your computer. This preserves your data in the rare case anything goes wrong. Second, the database is updated.', de = 'Die Aktualisierung besteht aus zwei einfachen Schritten. Zuerst wird eine Sicherheitskopie der Datenbank auf Ihrem Computer gespeichert. Dadurch bleiben Ihre Daten auch im unwahrscheinlichen Fall eines Fehlschlags erhalten. Anschlie&szlig;end wird die Datenbank aktualisiert.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'dbVersionText', en = 'Database version:', de = 'Datenbank-Version:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'legend', en = 'Steps to Update', de = 'Schritte zur Aktualisierung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'updateInformation', en = 'BADGER finance detected an update of its files. This page updates the database. All your data will be preserved.', de = 'BADGER finance hat eine Aktualisierung seiner Dateien festgestellt. Diese Seite aktualisiert die Datenbank. Ihre Daten bleiben vollst&auml;ndig erhalten.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'pageTitle', en = 'Update BADGER finance', de = 'BADGER finance aktualisieren'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'step2LinkText', en = 'Update database', de = 'Datenbank aktualisieren'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateProcedure', id = 'step2PostLink', en = '', de = ''");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'pageTitle', en = 'Updating BADGER finance', de = 'BADGER finance wird aktualisiert'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'betweenVersionsText', en = 'Versions in between:', de = 'Dazwischenliegende Versionen:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'preCurrentText', en = 'Update from', de = 'Aktualisierung von'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'postCurrentText', en = 'to', de = 'auf'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'postNextText', en = '', de = ''");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'logEntryHeader', en = 'Information from the update:', de = 'Informationen der Aktualisierung:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'updateInformation', en = 'BADGER finance is now performing the update. It is performed step-by-step, one step for each version.', de = 'Die Aktualisierung wird nun durchgef&uuml;hrt. Dies findet Schritt f&uuml;r Schritt statt, einen Schritt f&uuml;r jede Version.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'errorInformation', en = 'Please read the output of the process. If it encounters any severe errors they are written in red. In this case, please send the whole output to the BADGER development team (see help for contact info).', de = 'Bitte lesen sie die Ausgabe dieses Prozesses. Die einfachen Informationen sind auf Englisch gehalten. Falls der Prozess irgend welche schweren Fehler meldet, sind diese rot eingef&auml;rbt. Bitte schicken Sie in diesem Fall die gesamte Ausgabe an das BADGER Entwicklungsteam (siehe Hilfe f&uuml;r Kontaktinformationen).'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'updateFinished', en = 'The update has finished.', de = 'Die Aktualisierung ist beendet.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'severeError', en = 'The update encountered a severe error. Please send the whole output to the BADGER finance development team.', de = 'Die Aktualisierung stie&szlig; auf einen schweren Fehler. Bitte schicken Sie die gesamte Ausgabe an das BADGER finance development team.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'goToStartPagePreLink', en = 'Please ', de = 'Bitte '");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'goToStartPageLinkText', en = 'go to start page', de = 'zur Startseite gehen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'updateUpdate', id = 'goToStartPagePostLink', en = ' to continue.', de = ' um fortzusetzen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importExport', id = 'goToStartPagePreLink', en = 'Please ', de = 'Bitte '");
	$log .= doQuery("REPLACE i18n SET page_id = 'importExport', id = 'goToStartPageLinkText', en = 'go to start page', de = 'zur Startseite gehen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importExport', id = 'goToStartPagePostLink', en = ' to continue.', de = ' um fortzusetzen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importExport', id = 'newerVersion', en = 'Your backup file was from a previous version of BADGER finance. A database update will occur.', de = 'Ihre Sicherheitskopie war von einer vorherigen Version von BADGER finance. Es wird eine Datenbank-Aktualisierung stattfinden.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'DateFormats', id = 'mm/dd/yy', en = 'mm/dd/yy', de = 'mm/tt/jj'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics', id = 'showButton', en = 'Show', de = 'Anzeigen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'open', en = 'Open', de = '�ffnen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'gotoToday', en = 'Today', de = 'Heute'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Navigation', id = 'releaseNotes', en = 'Release Notes', de = 'Versionsgeschichte (englisch)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'welcome', id = 'pageTitle', en = 'Your accounts', de = 'Ihre Konten'");

	$log .= "&rarr; Updating old translation entries.\n";
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'mandatory_change_password_heading', en = 'You are currently using the BADGER standard password.<br />\r\nPlease change it.<br />\r\nSie k�nnen die Sprache von BADGER unter dem Men�punkt System / Preferences unter Language �ndern.', de = 'Sie verwenden momentan das BADGER Standardpasswort.<br />\r\nBitte �ndern Sie es.<br />\r\nYou can change the language of BADGER at menu System / Einstellungen, field Sprache.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'session_time_name', en = 'Session time (min):', de = 'Sessionl�nge (min):'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importExport', id = 'askImportVersionInfo', en = 'If you upload a backup created with a previous BADGER finance version an update to the current database layout will occur after importing. All your data will be preserved.', de = 'Falls Sie eine von einer vorherigen BADGER-finance-Version erstellten Sicherheitskopie hochladen, wird im Anschluss an den Import eine Datenbank-Aktualisierung auf die neueste Version stattfinden. All Ihre Daten bleiben erhalten.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importExport', id = 'insertSuccessful', en = 'Data successfully saved. Please use the password from the backup file to log in.', de = 'Die Daten wurden erfolgreich importiert. Bitte benutzen Sie das Passwort aus der Sicherheitskopie zum einloggen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'periodicalToolTip', en = 'This setting is used for automatic pocket money calculation. When calculating your pocket money from the past (i.e. your regular money spending habits), the BADGER will ignore all transactions marked &quot;periodical&quot; because it assumes that you have those already covered in the future recurring transactions. An example would be your rent. For the future rent, you have entered a recurring transactions. Past rent payments are flagged &quot;periodical transactions&quot; and not used for pocket money calculation.', de = 'Diese Wert wird bei der automatischen Taschengeldberechnung benutzt. Wenn der BADGER das Taschengeld der Vergangenheit (also Ihr Ausgabeverhalten) berechnet, ignoriert er periodische Transaktionen, da angenommen wird, dass diese �ber wiederkehrende Transaktionen in der Zukunft bereits erfasst sind. Ein Beispiel hierf�r ist die Miete: F�r die Zukunft wird die Miete �ber eine wiederkehrende Transaktion abgebildet, muss also nicht im Taschengeld ber�cksichtigt werden. In der Vergangenheit sind die Mietzahlungen periodische Transaktionen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'ExceptionalToolTip', en = 'This setting is used for automatic pocket money calculation. When calculating your pocket money from the past (i.e. your regular money spending habits), the BADGER will ignore all transactions marked &quot;exceptional&quot; because they do not resemble your usual spending habits. Examples would be a surprise car repair job, a new tv (unless you buy new tvs every month) or a holiday.', de = 'Diese Wert wird bei der automatischen Taschengeldberechnung benutzt. Wenn der BADGER das Taschengeld der Vergangenheit (also Ihr Ausgabeverhalten) berechnet, ignoriert er au�ergew�hnliche Transaktionen. Beispiele hierf�r sind eine gro�e Autoreparatur, ein neuer Fernseher (wenn man nicht jeden Monat einen neuen kauft) oder ein Urlaub.'");


	$log .= "&rarr; Inserting new menu entry for release notes.\n";
	$log .= doQuery("SELECT @max_navi_id := max(navi_id) FROM navi;");
	$log .= doQuery("INSERT INTO navi(navi_id, parent_id, menu_order, item_type, item_name, tooltip, icon_url, command) VALUES (@max_navi_id + 1, 28, 10, 'i', 'releaseNotes', '', 'information.gif', 'javascript:showReleaseNotes();')");
	$log .= "&rarr; Updating max id to navigation sequence table.\n";
	$log .= doQuery("UPDATE navi_ids_seq SET id = ((SELECT MAX(navi_id) FROM navi) + 1)");


	$log .= "&rarr; Updating demo account menu links.\n";
	$log .= doQuery("UPDATE user_settings SET prop_value = 's:2:\"35\";' WHERE prop_key = 'accountNaviId_3'");
	$log .= doQuery("UPDATE user_settings SET prop_value = 's:2:\"34\";' WHERE prop_key = 'accountNaviId_4'");

	$log .= "&rarr; Increasing security of session timeout.\n";
	$log .= doQuery("UPDATE user_settings SET prop_value = 's:2:\"30\";' WHERE prop_key = 'badgerSessionTime' AND prop_value = 's:4:\"9999\";'");

	$log .= "&rarr; Updating database version to 1.0 beta 2.\n";
	$log .= doQuery("REPLACE user_settings SET prop_key = 'badgerDbVersion', prop_value = 's:10:\"1.0 beta 2\";'");

	$log .= "\n&rarr;&rarr; Update to version 1.0 beta 2 finished. &larr;&larr;\n\n";

	return $log;
}

function update1_0beta2To1_0beta3() {
	global $badgerDb;

	$log = '';

	$log .= "&rarr; Adding page settings table.\n";
	$log .= doQuery(
		"CREATE TABLE IF NOT EXISTS `page_settings` (
		`page_name` VARCHAR(255) NOT NULL,
		`setting_name` VARCHAR(255) NOT NULL,
		`setting` TEXT NULL,
		PRIMARY KEY (`page_name`, `setting_name`)
		)", array(-1)
	);

	$log .= "&rarr; Adding new columns to account table.\n";
	$log .= doQuery(
		"ALTER TABLE `account` ADD `last_calc_date` DATE NOT NULL DEFAULT '1000-01-01',
		ADD `csv_parser` VARCHAR( 100 ) NULL,
		ADD `delete_old_planned_transactions` BOOL NULL", array(-1)
	);

	$log .= "&rarr; Adding new columns to category table.\n";
	$log .= doQuery(
		"ALTER TABLE `category` ADD `keywords` TEXT NULL,
		ADD `expense` BOOL NULL", array(-1)
	);


	$log .= "&rarr; Adding new datagrid handler.\n";
	$log .= doQuery("REPLACE datagrid_handler SET handler_name = 'MultipleAccounts', file_path = '/modules/statistics2/MultipleAccounts.class.php', class_name = 'MultipleAccounts'");

	$log .= "&rarr; Adding new columns to finished transaction table.\n";
	$log .= doQuery(
		"ALTER TABLE `finished_transaction` ADD `transferal_transaction_id` INT NULL,
		ADD `transferal_source` BOOL NULL", array(-1)
	);

	$log .= "&rarr; Adding new columns to planned transaction table.\n";
	$log .= doQuery(
		"ALTER TABLE `planned_transaction` ADD `transferal_transaction_id` INT NULL,
		ADD `transferal_source` BOOL NULL", array(-1)
	);

	$log .= "&rarr; Deleting unused translation entries.\n";
	$log .= doQuery("DELETE FROM i18n WHERE page_id = 'accountCategory' AND id = 'pageTitle'");
	$log .= doQuery("DELETE FROM i18n WHERE page_id = 'csv' AND id = 'title'");
	$log .= doQuery("DELETE FROM i18n WHERE page_id = 'csv' AND id = 'legend'");

	$log .= "&rarr; Adding new translation entries.\n";
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'pageTitleEdit', en = 'Edit Category', de = 'Kategorie bearbeiten'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'gotoToday', en = 'Today', de = 'Heute'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'pageTitleEdit', en = 'Edit Category', de = 'Kategorie bearbeiten'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'legend', en = 'Properties', de = 'Eigenschaften'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'filterLegend', en = 'Filter', de = 'Filter'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'setFilter', en = 'Set Filter', de = 'Filtern'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'resetFilter', en = 'Reset', de = 'Reset'");
	$log .= doQuery("REPLACE i18n SET page_id = 'common', id = 'gpcFieldUndefined', en = 'GET/POST/COOKIE field undefined', de = 'GET/POST/COOKIE-Feld nicht definiert'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'pageTitleNew', en = 'Create new Catagory', de = 'Neue Kategorie erstellen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'DataGridHandler', id = 'illegalFieldSelected', en = 'The following field is not known to this DataGridHandler:', de = 'Das folgende Feld ist diesem DataGridHandler nicht bekannt:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'MultipleAccounts', id = 'invalidFieldName', en = 'An unknown field was used with MultipleAccounts.', de = 'Es wurde ein unbekanntes Feld mit MultipleAccounts verwendet.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountAccount', id = 'deleteOldPlannedTransactions', en = 'Auto-insert recurring transactions:', de = 'Wiederkehrende Transaktionen automatisch eintragen:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountAccount', id = 'csvParser', en = 'CSV parser:', de = 'CSV-Parser:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountAccount', id = 'deleteOldPlannedTransactionsDescription', en = 'If this option is checked, every occuring instance of a recurring transaction is automatically inserted as an single transaction. Uncheck this if you import your transactions from a CSV file on a regular basis.', de = 'Wenn diese Option ausgew�hlt wurde, werden eintretende Instanzen einer wiederkehrenden Transaktion automatisch als einmalige Transaktionen eingetragen. W�hlen Sie die Option nicht aus, wenn Sie Ihre Transaktionen regelm��ig aus einer CSV-Datei importieren.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'range', en = 'Apply to', de = 'Anwenden auf'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'rangeAll', en = 'all', de = 'alle'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'rangeThis', en = 'this', de = 'diese'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'rangePrevious', en = 'this and previous', de = 'diese und vorherige'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'rangeFollowing', en = 'this and following', de = 'diese und folgende'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'rangeUnit', en = 'instances', de = 'Auspr�gungen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'plannedTransaction', id = 'afterTitle', en = 'after', de = 'nach'");
	$log .= doQuery("REPLACE i18n SET page_id = 'plannedTransaction', id = 'beforeTitle', en = 'before', de = 'vor'");
	$log .= doQuery("REPLACE i18n SET page_id = 'AccountManager', id = 'UnknownFinishedTransactionId', en = 'An unknown single transaction id was used.', de = 'Es wurde eine unbekannte ID einer einmaligen Transaktion verwendet.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'AccountManager', id = 'UnknownPlannedTransactionId', en = 'An unknown recurring transaction id was used.', de = 'Es wurde eine unbekannte ID einer wiederkehrenden Transaktion verwendet.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'transferalEnabled', en = 'Add transferal transaction', de = 'Gegenbuchung hinzuf�gen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'transferalAccount', en = 'Target account', de = 'Zielkonto'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'transferalAmount', en = 'Amount on target Account', de = 'Betrag auf Zielkonto'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'FinishedTransferalSourceTransaction', en = 'Source of single transferal transaction', de = 'Quelle einer Einmaligen Gegenbuchung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'FinishedTransferalTargetTransaction', en = 'Target of single transferal transaction', de = 'Ziel einer Einmaligen Gegenbuchung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'PlannedTransferalSourceTransaction', en = 'Source of recurring transferal transaction', de = 'Quelle einer Wiederkehrenden Gegenbuchung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'PlannedTransferalTargetTransaction', en = 'Target of recurring transferal transaction', de = 'Ziel einer Wiederkehrenden Gegenbuchung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCommon', id = 'includeSubCategories', en = '(including sub-categories)', de = '(Unterkategorien eingeschlossen)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'widgetEngine', id = 'noImage', en = 'An image file cannot be found in the current theme or the Standard theme.', de = 'Eine Bilddatei kann weder im aktuellen noch im Standardtheme gefunden werden.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'NavigationFromDB', id = 'noIcon', en = 'An navigation icon cannot be found in the current theme or the Standard theme.', de = 'Ein Navigationsicon kann weder im aktuellen noch im Standardtheme gefunden werden.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'keywordsLabel', en = 'Keywords', de = 'Schl�sselw�rter'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'keywordsDescription', en = 'If an imported transaction contains one of these keywords, this category will be pre-selected for this transaction. Use one line per keyword.', de = 'Wenn eine importierte Transaktion eines dieser Schl�sselw�rter enth�lt, wird diese Kategorie vor-ausgew�hlt. Geben Sie pro Schl�sselwort eine neue Zeile ein.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingDateDeltaLabel', en = 'Max. difference in days:', de = 'Max. Differenz in Tagen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingDateDeltaDescription', en = 'Only transactions that differ at most this amount of days from the imported transaction are considered for comparison.', de = 'Nur Transaktionen, die maximal diese Anzahl an Tagen von der importierten Transaktion abweichen, werden zum Vergleich herangezogen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingAmountDeltaLabel', en = 'Max. difference of amount (%)', de = 'Max. Abweichung des Betrags (%)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingAmountDeltaDescription', en = 'Only transactions that differ at most this percentage in amount from the imported transaction are considered for comparison.', de = 'Nur Transaktionen, deren Betrag maximal diesen Prozentsatz von der importierten Transaktion abweichen, werden zum Vergleich herangezogen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingTextSimilarityLabel', en = 'Min. text similarity (%)', de = 'Mind. Text�hnlichkeit (%)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingTextSimilarityDescription', en = 'Only transactions that are similar to the imported transaction by this percentage are considered for comparison.', de = 'Nur Transaktionen, die mindestens diesen Prozentsatz an �hnlichkeit zur importierten Transaktion aufweisen, werden zum Vergleich herangezogen.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'UserSettingsAdmin', id = 'matchingHeading', en = 'CSV Import Matching', de = 'Abgleich beim CSV-Import'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'matchingHeader', en = 'Similar Transactions', de = '�hnliche Transaktionen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'matchingToolTip', en = 'If you choose a transaction here, it will be replaced by the imported data.', de = 'Wenn Sie hier eine Transaktion ausw�hlen, wird sie durch die importierten Daten ersetzt.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'dontMatchTransaction', en = '&lt;Import as new&gt;', de = '&lt;Neu importieren&gt;'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'descriptionFieldImportedPartner', en = 'Imported transaction partner: ', de = 'Importierter Transaktionspartner: '");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'descriptionFieldOrigValutaDate', en = 'Original valuta date: ', de = 'Original-Buchungsdatum: '");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'descriptionFieldOrigAmount', en = 'Original amount: ', de = 'Original-Betrag: '");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountOverview', id = 'colBalance', en = 'Balance', de = 'Kontostand'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'colAccountName', en = 'Account', de = 'Konto'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'pageTitle', en = 'Advanced Statistics', de = 'Erweiterte Statistik'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'titleFilter', en = 'Title is ', de = 'Titel ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'descriptionFilter', en = 'Description is ', de = 'Beschreibung ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'valutaDateFilter', en = 'Valuta date is ', de = 'Buchungsdatum ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'valutaDateBetweenFilter', en = 'Valuta date is between ', de = 'Buchungsdatum ist zwischen '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'valutaDateBetweenFilterConj', en = ' and ', de = ' und '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'valutaDateBetweenFilterInclusive', en = ' (both inclusive)', de = ' (beide inklusive)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'valutaDateAgoFilter', en = 'Valuta within the last ', de = 'Buchungsdatum innerhalb der letzten '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'valutaDateAgoFilterDaysAgo', en = ' days', de = ' Tage'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'amountFilter', en = 'Amount is ', de = 'Betrag ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outsideCapitalFilter', en = 'Source is ', de = 'Quelle ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outsideCapitalFilterOutside', en = 'outside capital', de = 'Fremdkapital'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outsideCapitalFilterInside', en = 'inside capital', de = 'Eigenkapital'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'transactionPartnerFilter', en = 'Transaction partner is ', de = 'Transaktionspartner ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'categoryFilter', en = 'Category ', de = 'Kategorie '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'categoryFilterIs', en = 'is', de = 'ist'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'categoryFilterIsNot', en = 'is not', de = 'ist nicht'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'exceptionalFilter', en = 'Transaction is ', de = 'Transaktion ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'exceptionalFilterExceptional', en = 'exceptional', de = 'au�ergew�hnlich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'exceptionalFilterNotExceptional', en = 'not exceptional', de = 'nicht au�ergew�hnlich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'periodicalFilter', en = 'Transaction is ', de = 'Transaktion ist '");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'periodicalFilterPeriodical', en = 'periodical', de = 'regelm��ig'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'periodicalFilterNotPeriodical', en = 'not periodical', de = 'unregelm��ig'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersUnselected', en = 'Please choose a filter', de = 'Bitte w�hlen Sie einen Filter'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersTitle', en = 'Title', de = 'Titel'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersDescription', en = 'Description', de = 'Beschreibung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersValutaDate', en = 'Valuta date', de = 'Buchungsdatum'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersValutaDateBetween', en = 'Valuta date between', de = 'Buchungsdatum zwischen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersValutaDateAgo', en = 'Valuta date last days', de = 'Buchungsdatum vergangene Tage'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersAmount', en = 'Amount', de = 'Betrag'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersOutsideCapital', en = 'Outside capital', de = 'Fremdkapital'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersTransactionPartner', en = 'Transaction partner', de = 'Transaktionspartner'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersCategory', en = 'Category', de = 'Kategorie'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersExceptional', en = 'Exceptional', de = 'Au�ergew�hnlich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersPeriodical', en = 'Periodical', de = 'Regelm��ig'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'availableFiltersDelete', en = '&lt;Delete Filter&gt;', de = '&lt;Filter l�schen&gt;'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'filterCaption', en = 'Filters', de = 'Filter'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'twistieCaptionInput', en = 'Input Values', de = 'Eingabewerte'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTrendStartValue', en = 'Start Value', de = 'Startwert'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTrendStartValueZero', en = '0 (zero)', de = '0 (null)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTrendStartValueBalance', en = 'Account Balance', de = 'Kontostand'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTrendTickLabels', en = 'Tick labels', de = 'Tickmarken'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTrendTickLabelsShow', en = 'Show', de = 'Anzeigen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTrendTickLabelsHide', en = 'Hide', de = 'Verbergen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionCategoryType', en = 'Category Type', de = 'Kategorietyp'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionCategoryTypeInput', en = 'Income', de = 'Einnahmen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionCategoryTypeOutput', en = 'Spending', de = 'Ausgaben'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionCategorySubCategories', en = 'Sub-Categories', de = 'Unterkategorien'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionCategorySubCategoriesSummarize', en = 'Summarize sub-categories', de = 'Unterkategorien zusammenfassen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionCategorySubCategoriesNoSummarize', en = 'Do not summarize sub-categories', de = 'Unterkategorien einzeln auff�hren'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTimespanType', en = 'Type', de = 'Typ'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTimespanTypeWeek', en = 'Week', de = 'Woche'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTimespanTypeMonth', en = 'Month', de = 'Monat'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTimespanTypeQuarter', en = 'Quarter', de = 'Quartal'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionTimespanTypeYear', en = 'Year', de = 'Jahr'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionGraphType', en = 'Graph Type', de = 'Graphtyp'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionGraphTypeTrend', en = 'Trend', de = 'Verlauf'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionGraphTypeCategory', en = 'Category', de = 'Kategorie'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'outputSelectionGraphTypeTimespan', en = 'Timespan', de = 'Zeitvergleich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'twistieCaptionOutputSelection', en = 'Output Selection', de = 'Ausgabeauswahl'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'analyzeButton', en = 'Analyse', de = 'Analysieren'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'twistieCaptionGraph', en = 'Graph', de = 'Graph'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'twistieCaptionOutput', en = 'Output', de = 'Ausgabe'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'addFilterButton', en = 'Add Filter', de = 'Filter hinzuf�gen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2Graph', id = 'noMatchingTransactions', en = 'No transactions match your criteria.', de = 'Keine Transaktionen entsprechen Ihren Kriterien.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'beginsWith', en = 'begins with', de = 'f�ngt an mit'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'endsWith', en = 'ends with', de = 'h�rt auf mit'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'contains', en = 'contains', de = 'enth�lt'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'dateEqualTo', en = 'equal to', de = 'gleich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'dateBefore', en = 'before', de = 'vor'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'dateBeforeEqual', en = 'before or equal to', de = 'vor oder gleich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'dateAfter', en = 'after', de = 'nach'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'dateAfterEqual', en = 'after or equal to', de = 'nach oder gleich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'dateNotEqual', en = 'not equal to', de = 'ungleich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Navigation', id = 'Statistics2', en = 'Advanced Statistics', de = 'Erweiterte Statistik'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountAccount', id = 'csvNoParser', en = '&lt;No parser&gt;', de = '&lt;Kein Parser&gt;'");
	$log .= doQuery("REPLACE i18n SET page_id = 'PageSettings', id = 'SQLError', en = 'An SQL error occured attempting to fetch the PageSettings data from the database.', de = 'Beim Abrufen der PageSettings-Daten aus der Datenbank trat ein SQL-Fehler auf.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'pageSettingSave', en = 'Save Settings', de = 'Einstellungen speichern'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'pageSettingDelete', en = 'Delete Setting', de = 'Einstellung l�schen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'pageSettingsTwistieTitle', en = 'Settings', de = 'Einstellungen'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'pageSettingNewNamePrompt', en = 'Please enter the name for the setting:', de = 'Bitte geben Sie den Namen f�r die Einstellung ein:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'expenseRowLabel', en = 'Standard direction:', de = 'Standardgeldfluss:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'expenseIncome', en = 'Income', de = 'Einnahme'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'expenseExpense', en = 'Expense', de = 'Ausgabe'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountTransaction', id = 'categoryExpenseWarning', en = 'The selected category is marked as expense, but your amount is positive.', de = 'Die ausgew�hlte Kategorie ist als Ausgabe markiert, jedoch ist Ihr Betrag positiv.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2', id = 'miscCategories', en = '(Miscellaneous)', de = '(Verbleibende)'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'uploadTitle', en = 'File Uploaded and Analyzed', de = 'Datei hochgeladen und analysiert'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'submitTitle', en = 'CSV Data Imported', de = 'CSV-Daten importiert'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'pageHeading', en = 'CSV Import', de = 'CSV-Import'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'textday', en = 'day', de = 'Tag'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'textmonth', en = 'month', de = 'Monat'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'textweek', en = 'week', de = 'Woche'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'textyear', en = 'year', de = 'Jahr'");
	$log .= doQuery("REPLACE i18n SET page_id = 'Account', id = 'unknownOrdinalisationLanguage', en = 'An unknown language was passed to Account::ordinal().', de = 'An Account::ordinal wurde eine unbekannte Sprache �bergeben.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountOverviewPlanned', id = 'colRepeatText', en = 'Repetition', de = 'Wiederholung'");
	$log .= doQuery("REPLACE i18n SET page_id = 'statistics2Graph', id = 'only1transaction', en = 'Your criteria resulted in only one transaction, of which no line graph can be drawn.', de = 'Ihre Kriterien ergaben nur eine Transaktion, woraus kein Liniendiagramm gezeichnet werden kann.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'stringEqualTo', en = 'equals', de = 'gleich'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGridFilter', id = 'stringNotEqual', en = 'not equal', de = 'ungleich'");

	$log .= "&rarr; Changing translation entries.\n";
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'successfullyWritten', en = 'transaction(s) successfully written to the following accounts:', de = 'Transaktion(en) erfolgreich in die folgenden Konten geschrieben:'");
	$log .= doQuery("REPLACE i18n SET page_id = 'importCsv', id = 'noTransactionSelected', en = 'No transactions selected.', de = 'Keine Transaktionen ausgew�hlt.'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountCategory', id = 'pageTitleOverview', en = 'Transaction Categories', de = 'Transaktionskategorien'");
	$log .= doQuery("REPLACE i18n SET page_id = 'accountAccount', id = 'pageTitleOverview', en = 'Account Overview', de = 'Konten�bersicht'");
	$log .= doQuery("REPLACE i18n SET page_id = 'CategoryManager', id = 'no_parent', en = '&lt;No parent category&gt;', de = '&lt;Keine Elternkategorie&gt;'");
	$log .= doQuery("REPLACE i18n SET page_id = 'dataGrid', id = 'NoRowSelectedMsg', en = 'Please, select a row to edit', de = 'Bitte selektieren sie eine Zeile, die sie bearbeiten wollen.'");

	$sql = "SELECT count(navi_id) FROM navi WHERE item_name = 'Statistics2'";
	$result =& $badgerDb->query($sql);
	$arr = array();
	$result->fetchInto($arr, DB_FETCHMODE_ORDERED);
	if ($arr[0] == 0) {
		$log .= "&rarr; Inserting new menu entry for advanced statistics.\n";
		$log .= doQuery("SELECT @max_navi_id := max(navi_id) FROM navi;");
		$log .= doQuery("INSERT INTO navi(navi_id, parent_id, menu_order, item_type, item_name, tooltip, icon_url, command) VALUES (@max_navi_id + 1, 30, 5, 'i', 'Statistics2', '', 'statistics.gif', '{BADGER_ROOT}/modules/statistics2/statistics2.php')");
		$log .= "&rarr; Updating max id to navigation sequence table.\n";
		$log .= doQuery("UPDATE navi_ids_seq SET id = ((SELECT MAX(navi_id) FROM navi) + 1)");

		$log .= "&rarr; Updating menu order of forecast.\n";
		$log .= doQuery("UPDATE navi SET menu_order = 6 WHERE item_name = 'Forecast'");
	}

	$log .= "&rarr; Applying new recurring transaction mode.\n";
	$accountManager = new AccountManager($badgerDb);
	$now = new Date();
	$now->setHour(0);
	$now->setMinute(0);
	$now->setSecond(0);
	while ($currentAccount = $accountManager->getNextAccount()) {
		$currentAccount->expandPlannedTransactions($now);
	}

	$log .= "&rarr; Updating database version to 1.0 beta 3.\n";
	$log .= doQuery("REPLACE user_settings SET prop_key = 'badgerDbVersion', prop_value = 's:10:\"1.0 beta 3\";'");

	$log .= "\n&rarr;&rarr; Update to version 1.0 beta 3 finished. &larr;&larr;\n\n";

	return $log;
}

function update1_0beta3TO1_0() {
	$log = '';

	$log .= "&rarr; Updating session table.\n";
	doQuery("ALTER TABLE `session_master` CHANGE `id` `id` INT( 11 ) NULL");
	
	$log .= "&rarr; Adding spanish translation.\n";
	$log .= doQuery("INSERT INTO `langs` VALUES('es', 'spanish', 'spanish', 'not avaiable', 'iso-8859-1');", array(-1));	
	$log .= doQuery("ALTER TABLE `i18n` ADD `es` TEXT NULL ;", array(-1));	
	$log .= doQuery("DELETE FROM `i18n` WHERE NOT (page_id = 'Navigation' AND id REGEXP 'Account[0-9]+' );");
	
	$log .= doQuery("UPDATE `i18n` SET es = en WHERE page_id = 'Navigation' AND id REGEXP 'Account[0-9]+';");
	
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','gotoString','Go To Current Month','Gehe zu aktuellem Monat','Ir al mes actual');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','todayString','Today is','Heute ist','Hoy es');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','weekString','Wk','KW','Sem');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','scrollLeftMessage','Click to scroll to previous month. Hold mouse button to scroll automatically.','Klicken, um zum vorigen Monat zu gelangen. Gedr&uuml;ckt halten, um automatisch weiter zu scrollen.','Click para desplazarse al mes anterior. Mantenga presionado el bot�n del rat�n para desplazarse autom�ticamente. ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','scrollRightMessage','Click to scroll to next month. Hold mouse button to scroll automatically.','Klicken, um zum n&auml;chsten Monat zu gelangen. Gedr&uuml;ckt halten, um automatisch weiter zu scrollen.','Click para desplazarse al siguiente mes. Mantenga presionado el bot�n del rat�n para desplazarse autom�ticamente. ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','selectMonthMessage','Click to select a month.','Klicken, um Monat auszuw&auml;hlen','Click para seleccionar mes.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','selectYearMessage','Click to select a year.','Klicken, um Jahr auszuw&auml;hlen','Click para seleccionar a�o.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','selectDateMessage','Select [date] as date.','W&auml;hle [date] als Datum.','Selecciona [date] como fecha.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','closeCalendarMessage','Click to close the calendar.','Klicken, um den Kalender zu schlie&szlig;en.','Click to close the calendar.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','monthName','new Array(\\'January\\',\\'February\\',\\'March\\',\\'April\\',\\'May\\',\\'June\\',\\'July\\',\\'August\\',\\'September\\',\\'October\\',\\'November\\',\\'December\\')','new Array(\\'Januar\\',\\'Februar\\',\\'M&auml;rz\\',\\'April\\',\\'Mai\\',\\'Juni\\',\\'Juli\\',\\'August\\',\\'September\\',\\'Oktober\\',\\'November\\',\\'Dezember\\')','new Array(\\'Enero\\',\\'Febrero\\',\\'Marzo\\',\\'Abril\\',\\'Mayo\\',\\'Junio\\',\\'Julio\\',\\'Agosto\\',\\'Septiembre\\',\\'Octubre\\',\\'Noviembre\\',\\'Diciembre\\')');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','monthName2','new Array(\\'JAN\\',\\'FEB\\',\\'MAR\\',\\'APR\\',\\'MAY\\',\\'JUN\\',\\'JUL\\',\\'AUG\\',\\'SEP\\',\\'OCT\\',\\'NOV\\',\\'DEC\\')','new Array(\\'JAN\\',\\'FEB\\',\\'MRZ\\',\\'APR\\',\\'MAI\\',\\'JUN\\',\\'JUL\\',\\'AUG\\',\\'SEP\\',\\'OKT\\',\\'NOV\\',\\'DEZ\\')','new Array(\\'ENE\\',\\'FEB\\',\\'MAR\\',\\'ABR\\',\\'MAY\\',\\'JUN\\',\\'JUL\\',\\'AGO\\',\\'SEP\\',\\'OCT\\',\\'NOV\\',\\'DIC\\')');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','dayNameStartsWithMonday','new Array(\\'Mon\\',\\'Tue\\',\\'Wed\\',\\'Thu\\',\\'Fri\\',\\'Sat\\',\\'Sun\\')','new Array(\\'Mo\\',\\'Di\\',\\'Mi\\',\\'Do\\',\\'Fr\\',\\'Sa\\',\\'So\\')','new Array(\\'Lun\\',\\'Mar\\',\\'Mie\\',\\'Jue\\',\\'Vie\\',\\'Sab\\',\\'Dom\\')');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Calendar','dayNameStartsWithSunday','new Array(\\'Sun\\',\\'Mon\\',\\'Tue\\',\\'Wed\\',\\'Thu\\',\\'Fri\\',\\'Sat\\')','new Array(\\'So\\',\\'Mo\\',\\'Di\\',\\'Mi\\',\\'Do\\',\\'Fr\\',\\'Sa\\')','new Array(\\'Dom\\',\\'Lun\\',\\'Mar\\',\\'Mie\\',\\'Jue\\',\\'Vie\\',\\'Sab\\')');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badgerException','Errorcode','Error code','Fehlermeldung','C�digo de error');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badgerException','Error','Error','Fehler','Error');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badgerException','Line','Line','Zeile','Linea');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','fullYear','Full year','ganzes Jahr','A�o completo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Logout','Logout','Abmelden','Salir');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Preferences','Preferences','Einstellungen','Preferencias');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('html2pdf','missing_url','No Source URL to create a PDF document from.','Quell-URL zum Generieren des PDFs nicht �bergeben.','No se encuentra URL de origen para crear un documento PDF.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','AccountManager','Accounts overview','Konten�bersicht','Cuentas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','upload','Upload','Upload','Subir');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','noSeperator','File cannot be read by this parser. No seperator found','Datei kann mit diesem Parser nicht gelesen werden. Kein Trennzeichen gefunden','El archivo no puede ser le�do por este analizador. No se encontr� separador,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','selectFile','Please select your CSV file','Bitte w�hlen Sie die CSV Datei aus','Por favor seleccione su archivo CSV');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','selectParser','Select Input Parser','CSV Format w�hlen','Seleccione analizador de entrada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','date_format_description','Sets the date format to be used.','Legt das zu verwendende Datumsformat fest.','Establece el formato de fecha que se utilizar�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','change_password_heading','Change Password','Passwort �ndern','Cambiar contrase�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','maximum_login_attempts_name','Maximum Login Attempts:','Maximale Loginversuche:','M�ximos intentos de acceso:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','new_password_confirm_description','Please confirm your entered password here.','Hier bitte das eingegebene Passwort best�tigen.','Por favor, confirme su contrase�a aqu�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','maximum_login_attempts_description','After how many failed login attempts should the access be temporarily denied?','Nach wie vielen fehlgeschlagenen Loginversuchen wird der Zugang tempor�r gesperrt?','Despu�s de cu�ntos intentos de acceso deber�a ser denegado temporalmente el acceso?');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','linktext_after_successful_mandatory_change','Continue work...','Weiter...','Continua trabajando...');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','lock_out_time_description','How many seconds should the access be denied?','Wie viele Sekunden wird die Sperre des Logins aufrecht erhalten?','Por cu�ntos segundos deber�a denegarse el acceso?');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','lock_out_time_name','Duration of Lockout (sec):','Dauer der Zugangssperre (Sek.):','Duraci�n de bloqueo (seg):');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','mandatory_change_password_heading','You are currently using the BADGER standard password.<br /> Please change it.<br /> Sie k�nnen die Sprache von BADGER unter dem Men�punkt System / Preferences unter Language �ndern.','Sie verwenden momentan das BADGER Standardpasswort.<br /> Bitte �ndern Sie es.<br /> You can change the language of BADGER at menu System / Einstellungen, field Sprache.','You are currently using the BADGER standard password.<br />Please change it.<br />Sie k�nnen die Sprache von BADGER unter dem Men�punkt System / Preferences unter Language �ndern.<br />Est� utilizando la contrase�a BADGER est�ndar.<br />Por favor, c�mbiela.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','language_name','Language:','Sprache:','Idioma:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','linktext_after_failed_mandatory_change','Try again...','Nochmal versuchen...','Int�ntelo de nuevo...');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','orderParamNoArray','The parameter to DataGridHandler::setOrder() is no array!','Der Parameter von DataGridHandler::setOrder() ist kein Array!','El par�metro a DataGridHandler::setOrder() no es array!');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','orderArrayElementNoArray','The array passed to DataGridHandler::setOrder() contains a non-array element at index:','Das an DataGridHandler::setOrder() �bergebene Array enth�lt an folgendem Index ein Nicht-Array-Element:','El array pasado a DataGridHandler::setOrder() no  contiene un elemento de array en el �ndice:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','orderKeyIndexNotDefined','The index ''key'' is not defined in the following element of the parameter to DataGridHandler::setOrder():','Der Index ''key'' ist im folgenden Element des Parameters von DataGridHandler::setOrder() nicht definiert:','El �ndice ''key'' no est� definido en el siguiente elemento del par�metro a  DataGridHandler::setOrder():');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','orderDirIndexNotDefined','The index ''dir'' is not defined in the following element of the parameter to DataGridHandler::setOrder():','Der Index ''dir'' ist im folgenden Element des Parameters von DataGridHandler::setOrder() nicht definiert:','El �ndice ''dir'' no est� definido en el siguiente elemento del par�metro a DataGridHandler::setOrder():');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','orderIllegalField','The following field is not known to this DataGridHandler:','Das folgende Feld ist diesem DataGridHandler nicht bekannt:','El siguiente campo no es conocido para este DataGridHandler:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','orderIllegalDirection','The following illegal order direction was passed to DataGridHandler:','Die folgende ung�ltige Sortierrichtung wurde an DataGridHandler �bergeben:','La siguiente orden ilegal direcci�n fue aprobada para DataGridHandler: ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','filterParamNoArray','The parameter to DataGridHandler::setFilter() is no array!','Der Parameter von DataGridHandler::setFilter() ist kein Array!','El par�metro a DataGridHandler::setFilter() no es array!');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','filterArrayElementNoArray','The array passed to DataGridHandler::setFilter() contains a non-array element at index:','Das an DataGridHandler::setFilter() �bergebene Array enth�lt an folgendem Index ein Nicht-Array-Element:','El array pasado a DataGridHandler::setFilter() no  contiene un elemento de array en el �ndice:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','filterKeyIndexNotDefined','The index ''key'' is not defined in the following element of the parameter to DataGridHandler::setFilter():','Der Index ''key'' ist im folgenden Element des Parameters von DataGridHandler::setFilter() nicht definiert:','El �ndice ''key'' no est� definido en el siguiente elemento del par�metro a  DataGridHandler::setFilter():');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','filterOpIndexNotDefined','The index ''op'' is not defined in the following element of the parameter to DataGridHandler::setFilter():','Der Index ''op'' ist im folgenden Element des Parameters von DataGridHandler::setFilter() nicht definiert:','El �ndice ''op'' no est� definido en el siguiente elemento del par�metro a DataGridHandler::setFilter():');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','filterValIndexNotDefined','The index ''val'' is not defined in the following element of the parameter to DataGridHandler::setFilter():','Der Index ''val'' ist im folgenden Element des Parameters von DataGridHandler::setFilter() nicht definiert:','El �ndice ''val'' no est� definido en el siguiente elemento del par�metro a DataGridHandler::setFilter():');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','filterIllegalField','The following field is not known to this DataGridHandler:','Das folgende Feld ist diesem DataGridHandler nicht bekannt:','El siguiente campo no es conocido para este DataGridHandler:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('AccountManager','invalidFieldName','The following field is not known to AccountManager:','Das folgende Feld ist AccountManager nicht bekannt:','El siguiente campo no es conocido para  AccountManager:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('AccountManager','SQLError','An SQL error occured attempting to fetch the AccountManager data from the database:','Beim Abrufen der AccountManager-Daten aus der Datenbank trat ein SQL-Fehler auf:','Se ha producido un error SQL al intentar obtener la AccountManager  de la base de datos:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettings','illegalKey','The following key is not defined in UserSettings:','Der folgende Schl�ssel wurde in UserSettings nicht definiert:','La siguiente clave no est� definida en UserSettings: ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridRepository','illegalHandlerName','The following DataGridHandler is not known to BADGER:','Der folgende DataGridHandler ist BADGER nicht bekannt:','El siguiente DataGridHandler no es conocido para BADGER:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridXML','undefinedColumns','DataGridXML::getXML() was called without setting columns!','DataGridXML::getXML() wurde aufgerufen, ohne vorher die Spalten zu definieren!','DataGridXML::getXML() fue llamada sin establecer columnas!');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridXML','XmlSerializerException','An error occured in DataGridXML::getXML() while transforming internal data to XML.','Beim Umwandeln von internen Daten in XML trat in DataGridXML::getXML() ein Fehler auf.','Se ha producido un error en DataGridXML::getXML() durante la transformaci�n interna de datos a XML.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','language_description','Sets the language to be used.','Legt die zu verwendende Sprache fest.','Establece el idioma que se utilizar�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','new_password_confirm_name','Confirm new password:','Neues Passwort best�tigen:','Confirmar la nueva contrase�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','submit_button','Submit','Senden','Enviar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','new_password_description','If you want to set a new password, please enter it here.','Falls sie ein neues Passwort festlegen wollen, geben Sie es hier ein.','Si desea establecer una nueva contrase�a, por favor, introd�zcala aqu�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','you_are_logout','You have successfully logged out.','Sie haben sich erfolgreich ausgeloggt.','Has cerrado la sesi�n. ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','locked_out_refresh','Ban over?','Sperre schon vorr�ber?','Bloqueo?');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','sent_password_failed','An error occured during sendig of the e-mail.','Beim Senden der E-Mail trat ein Fehler auf.','Se ha producido un error duranteel env�o del e-mail.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','locked_out_part_2','seconds.','Sekunden.','segundos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','locked_out_part_1','Because of too many failed login attempts you cannot login right now.<br/>The ban will be in effect for another','Aufgrund zu h�ufiger fehlgeschlagener Loginversuche k�nnen sie sich leider derzeit nicht einloggen.<br/>Diese Sperre besteht noch f�r weitere','Debido a demasiados intentos de acceso no se puede acceder ahora mismo.<br />El bloqueo estar� vigente por otro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','ask_really_send_link','Send the new password!','Neues Passwort schicken!','Enviar la nueva contrase�a!');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','sent_password','A new password was sent to your e-mail adresse.','Ein neues Passwort wurde an die hinterlegte E-Mail Adresse gesendet.','La nueva contrase�a fue enviada a tu e-mail,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','ask_really_send','Really send a new password? Your old password will no longer work.','M�chten Sie sich wirklich ein neues Passwort zuschicken lassen? Ihr altes Passwort wird hiermit ung�ltig.','Realmente enviar una nueva contrase�a? Su antigua contrase�a ya no funcionar�,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','empty_password','Error: No password submitted!','Fehler: Kein Passwort eingegeben!','Error: Contrase�a no enviada!');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','header','Login','Einloggen','Entrar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','wrong_password','Error: Wrong Password!','Fehler: Falsches Passwort!','Error: Contrase�a incorrecta!');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','forgot_password','Forgot your password?','Passwort vergessen?','�Olvid� su contrase�a?');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','enter_password','Please enter your password:','Bitte geben Sie ihr Passwort ein:','Por favor, introduzca su contrase�a:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','targetAccount','Please select your target account','Bitte w�hlen Sie das Zielkonto aus','Por favor seleccione su  cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','wrongSeperatorNumber','File cannot be read by this parser. At least 1 line has not the right number of seperators','Datei kann mit diesem Parser nicht gelesen werden. Mindestens 1 Zeile enth�lt nicht die richtige Anzahl an Trennzeichen','El archivo no puede ser le�do por este analizador. Al menos 1 l�nea no tiene correcto el n�mero de separadores');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','select','Transfer','�bernehmen','Transferencia');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','category','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','account','Account','Konto','Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','title','Title','Verwendungszweck','T�tulo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','description','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','valutaDate','Valuta Date','Buchungsdatum','Fecha');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','amount','Amount','Betrag','Cantidad');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','transactionPartner','Transaction Partner','Transaktionspartner','Socio de Transacci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','save','Write to Database','In Datenbank schreiben','Escribir a la base de datos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','successfullyWritten','transaction(s) successfully written to the following accounts:','Transaktion(en) erfolgreich in die folgenden Konten geschrieben:','transacci�n(es) escrita(s) con �xito a las siguientes cuentas:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','noTransactionSelected','No transactions selected.','Keine Transaktionen ausgew�hlt.','No hay transacciones seleccionadas.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','invalidFieldName','The following field is not known to Account:','Das folgende Feld ist Account nicht bekannt:','El siguiente campo no es conocido para la cuenta:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','SQLError','An SQL error occured attempting to fetch the Account data from the database:','Beim Abrufen der Account-Daten aus der Datenbank trat ein SQL-Fehler auf:','Se ha producido un error SQL al intentar obtener los datos de la cuenta de la base de datos:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','invalidFieldName','An unknown field was used in CategoryManager.','Im CategoryManager wurde ein ung�ltiges Feld verwendet.','Un campo desconocido fue usado en CategoryManager.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','SQLError','An SQL error occured attempting to fetch the CategoryManager data from the database.','Beim Abrufen der CategoryManager-Daten aus der Datenbank trat ein SQL-Fehler auf.','Se ha producido un error SQL al intentar obtener datos de CategoryManager  de la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','UnknownFinishedTransactionId','An unknown id was used for a single transaction.','Es wurde eine unbekannte ID einer einmaligen Transaktion benutzt.','Una ID desconocida fue usada para una transacci�n simple.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','insertError','An error occured while inserting a new single transaction into the database.','Beim Einf�gen einer neuen einmaligen Transaktion trat ein Fehler auf.','Se ha producido un error durante  la inserci�n de una nueva operaci�n �nica en la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('AccountManager','UnknownAccountId','An unknown id of an account was used.','Es wurde eine unbekannte ID eines Kontos benutzt.','Fue usada una ID desconocida de una cuenta.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('AccountManager','insertError','An error occured while inserting a new account in the database.','Beim Einf�gen eines neuen Kontos trat ein Fehler auf.','Se ha producido un error durante la inserci�n de una nueva cuenta en la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('FinishedTransaction','SQLError','An SQL error occured attempting to edit the single transaction data in the database.','Beim Bearbeiten der Daten einer einmaligen Transaktion in der Datenbank trat ein SQL-Fehler auf.','Se ha producido un error SQL al tratar de editar una transacci�n simple en la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','UnknownCategoryId','An unknown id of a category was used.','Es wurde eine unbekannte ID einer Kategorie benutzt.','Fue usada una ID desconocida de una categor�a.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','insertError','An error occured while inserting a new category in the database.','Beim Einf�gen einer neuen Kategorie trat ein Fehler auf.','Se ha producido un error durante la inserci�n de una nueva categor�a en la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Category','SQLError','An SQL error occured attempting to edit the Category data in the database:','Beim Bearbeiten der Category-Daten in der Datenbank trat ein SQL-Fehler auf:','Se ha producido un error al intentar editar la categor�a en la base de datos:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','periodical','Periodical','Regelm��ig','Frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','Exceptional','Exceptional','Au�ergew�hnlich','Excepcional');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','toolTipParserSelect','Choice of the csv parser. If your bank is not available or if there is a error when you upload, please visit our homepage. There perhaps you can find a proper parser or get support.','Auswahl des CSV Parsers. Wenn Ihre Bank nicht vorhanden ist oder es beim Upload zu Fehlern kommt, schauen Sie bitte auf unsere Website. Dort gibt es evtl. den passenden Parser oder Support.','Elecci�n del analizador csv. Si su banco no est� disponible o si hay un error al subirlo usted, por favor, visite nuestra web. Tal vez pueda encontrar un buen analizador o recibir apoyo.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('intervalUnits','day','day','Tag','d�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('intervalUnits','week','week','Woche','semana');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('intervalUnits','month','month','Monat','mes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('intervalUnits','year','year','Jahr','A�o completo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('intervalUnits','every','every','jede(n)/(s)','cada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','toolTopAccountSelect','Your accounts. You can administrate your accounts in the account manager.','Ihre Konten. �nderungen k�nnen Sie in der Kontoverwaltung vornehmen.','Sus cuentas. Usted puede administrar sus cuentas en la cuenta de administrador.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('templateEngine','noTemplate','Template not found.','Template nicht gefunden.','No se encuentra la plantilla.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('widgetsEngine','ToolTipJSNotAdded','Method \$widgets->addToolTipJS(); has not been evoked.','Die Methode \$widgets->addToolTipJS(); wurde nicht vorher aufrufen.','El m�todo  \$widgets->addToolTipJS(); no ha sido evocado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('widgetsEngine','ToolTipLayerNotAdded','The method echo \$widgets->addToolTipLayer(); has not been evoked.','Die Methode echo \$widgets->addToolTipLayer(); wurde nicht vorher vorher aufrufen.','El m�todo echo \$widgets->addToolTipLayer(); no ha sido evocado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('widgetsEngine','CalendarJSNotAdded','The method \$widgets->addCalendarJS(); has not been evoked.','Die Methode \$widgets->addCalendarJS(); wurde nicht vorher vorher aufrufen.','El m�todo \$widgets->addCalendarJS(); no ha sido evocado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('widgetsEngine','AutoCompleteJSNotAdded','The method \$widgets->addAutoCompleteJS(); has not been evoked.','Die Methode \$widgets->addAutoCompleteJS(); wurde nicht vorher vorher aufrufen.','El m�todp \$widgets->addAutoCompleteJS(); no ha sido evocado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','FinishedTransaction','Single Transaction','Einmalige Transaktion','Transacci�n Simple');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','PlannedTransaction','Recurring transaction','Wiederkehrende Transaktion','Transacci�n Frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','day','daily','t�glich','diario');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','week','weekly','w�chentlich','semanal');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','month','monthly','monatlich','mensual');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','year','yearly','j�hrlich','anual');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','UnknownPlannedTransactionId','An unknown id of a recurring transaction was used.','Es wurde eine unbekannte ID einer wiederkehrenden Transaktion benutzt.','Fue usada una ID desconocida de una transacci�n frecuente.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','IllegalRepeatUnit','An illigeal unit was given for a recurring transaction.','F�r eine wiederkehrende Transaktion wurde eine ung�ltige Wiederholungseinheit angegeben.','Fue dada una unidad ilegal para una transacci�n frecuente.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','illegalPropertyKey','An unknown property key was used for an account.','F�r ein Konto wurde ein ung�ltiger Eigenschaftsschl�ssel verwendet.','Fue usada una clave de propiedad desconocida para una cuenta.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','outsideCapital','Outside capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','error_standard_password','Please don�t use the standard password.','Bitte nicht das Standardpasswort verwenden.','Por favor no use la contrase�a st�ndar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','session_time_name','Session time (min):','Sessionl�nge (min):','Tiempo de sesi�n (min):');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','site_name','User Settings','Einstellungen','Configuraci�n de usuario');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','submit_button','Save','Speichern','Guardar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','user_settings_heading','User Settings','Einstellungen','Configuraci�n de usuario');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DateFormats','dd.mm.yyyy','dd.mm.yyyy','tt.mm.jjjj','dd.mm.aaaa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DateFormats','dd/mm/yyyy','dd/mm/yyyy','tt/mm/jjjj','dd/mm/aaaa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DateFormats','dd-mm-yyyy','dd-mm-yyyy','tt-mm-jjjj','dd-mm-aaaa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DateFormats','yyyy-mm-dd','yyyy-mm-dd','jjjj-mm-tt','aaaa-mm-dd');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DateFormats','yyyy/mm/dd','yyyy/mm/dd','jjjj/mm/tt','aaaa/mm/dd');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Currency','SQLError','An SQL error occured attempting to edit the Currency data in the database:','Beim Bearbeiten der W�hrungs-Daten in der Datenbank trat ein SQL-Fehler auf:','Se ha producido un error SQL al intentar editar la Moneda en la base de datos:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CurrencyManager','invalidFieldName','An unknown field was used in CurrencyManager.','Im CurrencyManager wurde ein ung�ltiges Feld verwendet.','Fue usado un campo desconocido en CurrencyManager');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CurrencyManager','SQLError','An SQL error occured attempting to fetch the CurrencyManager data from the database.','Beim Abrufen der CurrencyManager-Daten aus der Datenbank trat ein SQL-Fehler auf.','Se ha producido un error SQL al intentar obtener el CurrencyManager de la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CurrencyManager','UnknownCurrencyId','An unknown id of a currency was used.','Es wurde eine unbekannte ID einer W�hrung benutzt.','Fue usado un ID desconocido de una divisa,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CurrencyManager','insertError','An error occured while inserting a new currency in the database.','Beim Einf�gen einer neuen W�hrung trat ein Fehler auf.','Se ha producido un error SQL al intentar editar la Moneda en la base de datos:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('PlannedTransaction','SQLError','An SQL error occured attempting to edit the recurring transactions data in the database.','Beim Bearbeiten der Daten einer wiederkehrenden Transaktion in der Datenbank trat ein SQL-Fehler auf.','Se ha producido un error SQL al tratar de editar la  transaccion recurrente en la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('templateEngine','HeaderIsAlreadyWritten','XHTML Head is already added to the document. This function has to be called before writing the header.','Der XHTML Kopf wurde bereits in das Dokument eingef�gt. Die Funktion muss vor der Ausgabe aufgerufen werden.','XHTML Head ya est� a�adido al documento. Esta funci�n tiene que ser llamada antes de escribir la cabecera.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('widgetsEngine','HeaderIsNotWritten','XHTML Header isn''t added to the document. Please call \$tpl->getHeader() before this function.','Der XHTML Kopf wurde noch nicht in das Dokument eingef�gt. Die Funktion \$tpl->getHeader() muss vor dieser Funktion aufgerufen werden.','XHTML Header no se a�ade al documento. Por favor llame a \$tpl->getHeader() antes de esta funci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','noNewTransactions','No new transactions found in the csv file.','Keine neuen Transaktionen in der CSV Datei gefunden.','No hay nuevas transacciones en el archivo csv.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','echoFilteredTransactionNumber','transactions were filtered because they were already in the database.','Transaktionen gefiltert, da sie bereits in der Datenbank vorhanden sind.','transactions were filtered because they were already in the database.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askTitle','Import / Export Data','Daten Import / Export','Importar / Exportar Datos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askExportTitle','Export / Backup','Export / Datensicherung','Exportar / Copia de seguridad');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askExportText','You can save all of your BADGER finance data in a file. This file will be transmitted to your computer. Save the File at a secure place.','Sie k�nnen Ihre gesamten BADGER finance Daten in eine Datei sichern. Diese wird direkt auf Ihren Rechner �bertragen. Speichern Sie die Datei ab.','Puede guardar todas sus finanzas de BADGER en un archivo. Este fichero ser� transmitido a su equipo. Guarde el archivo en un lugar seguro.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askExportAction','Export','Exportieren','Exportar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportTitle','Import','Import','Importar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportInfo','You can upload previously saved backup data into BADGER finance.','Sie k�nnen einen einmal gesicherten Stand der BADGER finance Daten von einer Datei auf Ihrem Rechner zur�ck an BADGER finance �bertragen.','Puede subir una copia de seguridad guardada anteriormente en BADGER,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportWarning','Warning! When uploading a backup, all current data will be lost and replaced by data from the backup file.','Achtung: Beim Import gehen alle bereits vorhandenen Daten in BADGER finance verloren!','�Advertencia! Al subir una copia de seguridad, todos los datos se perder�n y se sustituir�n por los datos del archivo de copia de seguridad.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportVersionInfo','If you upload a backup created with a previous BADGER finance version an update to the current database layout will occur after importing. All your data will be preserved.','Falls Sie eine von einer vorherigen BADGER-finance-Version erstellten Sicherheitskopie hochladen, wird im Anschluss an den Import eine Datenbank-Aktualisierung auf die neueste Version stattfinden. All Ihre Daten bleiben erhalten.','Si sube una copia de seguridad creada con una versi�n anterior de BADGER, una actualizaci�n de la base de datos actual se llevar� a cabo despu�s de la importaci�n. Todos sus datos ser�n conservados.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportCurrentVersionInfo','You have the following version of BADGER finance currently installed:','Die aktuelle Version von BADGER finance ist:','Usted tiene la siguiente versi�n de BADGER instalada actualmente:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportAction','Import','Importieren','Importar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportNo','No, I do not want to upload the backup data.','Nein, ich m�chte die Daten nicht importieren.','No, no quiero cargar los datos de la copia de seguridad.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportYes','Yes I want to upload the backup file. All data will be deleted and replaced by the data from the backup file.','Ja, ich m�chte die Daten importieren. Alle bestehenden Daten werden dabei gel�scht und durch den alten Datenbestand aus der Backup-Datei ersetzt.','S� quiero cargar el archivo de la copia de seguridad. Todos los datos ser�n eliminados y sustituidos por los datos del archivo de la copia de seguridad.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportFile','Please browse for your backup file:','Bitte w�hlen Sie die Sicherungsdatei aus:','Por favor, busque por su archivo de copia de seguridad:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askImportSubmitButton','Import','Importieren','Importar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','askInsertTitle','Data Recovery','Datenwiederherstellung','Recuperaci�n de Datos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','insertTitle','Import','Import','Importar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','insertNoInsert','You chose not to import the backup data.','Sie haben sich entschieden, die Daten nicht zu importieren.','Usted opt� por no importar los datos de copia de seguridad.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','insertSuccessful','Data successfully saved. Please use the password from the backup file to log in.','Die Daten wurden erfolgreich importiert. Bitte benutzen Sie das Passwort aus der Sicherheitskopie zum einloggen.','Datos guardados con �xito. Por favor, utilice la contrase�a del archivo de la copia de seguridad para acceder.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','noSqlDumpProvided','Uploaded file missing.','Es wurde keine Datei hochgeladen.','Se perdi� el archivo cargado');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','errorOpeningSqlDump','There was a problem processing the uploaded file.','Die hochgeladene Datei konnte nicht verarbeitet werden.','Se ha producido un problema de procesamiento del archivo subido.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','incompatibleBadgerVersion','The uploaded file was not a BADGER finance file or a BADGER finance backup file from an uncompatible version.','Die hochgeladene Datei ist kein BADGER finance Export oder von einer inkompatiblen BADGER finance Version.','El archivo subido no era un archivo BADGER o era una versi�n incompatible de copia de seguridad BADGER.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','SQLError','There was an Error during execution of the SQL-statement.','Beim Verarbeiten eines SQL-Befehls ist ein Fehler aufgetreten.','Se ha producido un error durante la ejecuci�n de la declaraci�n SQL.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','insertNoFile','Error: You did not upload a file.','Fehler: SIe haben keine Datei hochgeladen.','Error: No se ha cargado un archivo.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','CurrencyManager','Currencies','W�hrungen','Divisas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','System','System','System','Sistema');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Backup','Backup','Backup','Copia de seguridad');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','CSV-Import','Import transactions','Transaktionen importieren','Importar transacciones');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Forecast','Forecast','Prognose','Pron�stico');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','title','Category name','Kategoriename','Nombre de la categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','description','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','title','Account name','Kontoname','Nombre de la cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','description','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','outsideCapital','Outside capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','lowerLimit','lower limit','Untergrenze','l�mite inferior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','upperLimit','upper limit','Obergrenze','l�mite superior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','parent','Parent category','Elternkategorie','Categor�a padre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','balance','Balance','Gesamtkontostand','Balance');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','currency','Currency','W�hrung','Divisa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','description','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','valutaDate','Valuta date','Buchungsdatum','Fecha');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','targetFutureCalcDate','Target future calc date','Stichtag','Objetivo para el futuro calc fecha');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','amount','Amount','Betrag','Cantidad');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','outsideCapital','Outside capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','transactionPartner','Transaction partner','Transaktionspartner','Transacci�n Padre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','category','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','periodical','Periodical transaction','Periodische Transaktionen','Transacci�n Frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','exceptional','Exceptional transaction','Au�ergew�hnliche Transaktion','Transacci�n Excepcional');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','symbol','Currency symbol','W�hrungsk�rzel','S�mbolo de la moneda');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','longname','Written name of the currency','W�hrungsname','Escribir nombre de la moneda');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','deleteMsg','Do you really want to delete the selected records?','Wollen sie die selektierten Datens�tze wirklich l�schen?','�Est� seguro de que desea eliminar el registro seleccionado?');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','rowCounterName','row(s)','Datens�tze','fila(s)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','new','New','Neu','Nuevo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','delete','Delete','L�schen','Eliminar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','date_format_name','Date Format:','Datumsformat:','Formato de fecha:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','error_confirm_failed','The passwords don�t match.','Die Passw�rter stimmen nicht �berein.','Las contrase�as no coinciden');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','error_empty_password','Password mus have at least one letter.','Passwort muss mindestens ein Zeichen haben.','La contrase�a debe tener al menos una letra.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','error_old_password_not_correct','Old password not correct.','Altes Passwort nicht korrekt.','La antigua contrase�a no es correcta.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','new_password_name','New password:','Neues Passwort:','Nueva contrase�a:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','old_password_description','Please enter your old password.','Bitte geben Sie ihr altes Passwort an.','Por favor, introduzca su antigua contrase�a.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','old_password_name','Old password:','Altes Passwort:','Contrase�a antigua');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','password_change_commited','Password was changed successfully.','Passwort wurde erfolgreich ge�ndert.','La contrase�a fue cambiada con �xito.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','seperators_description','Sets the number format to be used.','Legt das zu verwendende Zahlenformat fest.','Establezca el formato de n�mero que se utilizar�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','seperators_name','Seperators:','Trennzeichen:','Separadores:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','session_time_description','Defines after how much time of inactivity a new login is neccessary.','Legt fest, nach wie langer Inaktivit�t ein erneutes Login n�tig ist.','Define despu�s de cu�nto tiempo de inactividad, un nuevo inicio de sesi�n ser� necesaria.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','start_page_description','Defines the page to display at the start of BADGER.','Legt die Seite fest, die beim Start vom BADGER angezeigt wird.','Define la p�gina para mostrar al inicio de BADGER');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','start_page_name','Start page:','Startseite:','P�gina de inicio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','template_description','A theme determines the look of BADGER finance.','Ein Theme bestimmt das grundlegende Aussehen von BADGER finance.','Un tema determina el aspecto de BADGER');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','template_name','Theme:','Theme:','Tema:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','user_settings_change_commited','User settings have been successfully commit','Nutzereinstellungen wurden erfolgreich gespeichert.','La configuraci�n de usuario ha sido establecida.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','login_button','Login','Login','Entrar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','fs_heading','Login','Login','Entrar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','fs_heading','User Settings','Allgemeine Einstellungen','Configuraci�n de usuario.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','mandatory_fs_heading','Password Change','Passwort�nderung','Cambiar contrase�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','mandatory_commited_fs_heading','Password Changed','Passwort ge�ndert','Contrase�a cambiada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Statistics','Statistics','Statistiken','Estad�sticas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','save','Save','Speichern','Guardar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','gotoToday','Today','Heute','Hoy');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','LoadingMessage','Loading ...','Lade ...','Cargando...');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','CategoryManager','Transaction categories','Transaktionskategorien','Categor�as de transacciones');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Help','Help','Hilfe','Ayuda');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','About','About Badger','�ber Badger','Acerca de Badger');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Analysis','Analysis','Auswertung','An�lisis');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Accounts','Accounts','Konten','Cuentas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Documentation','Documentation','Dokumentation','Documentaci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Print','Print','Drucken','Imprimir');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','PrintView','Print view','Druckansicht','Vista de impresi�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','PrintPDF','Save as PDF','Als PDF speichern','Guardar como PDF');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','noAccountID','noAccountID','es wurde keine AccountID �bermittelt','No se encuentra la AccountID');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','toolTipAccountSelect','Please choose the account for the forecast','Bitte w�hlen Sie das Konto f�r den Forecast','Por favor, elija la cuenta a pronosticar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','sendData','Create chart','Diagramm erstellen','Crear gr�fico');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','One-time Transaction','FinishedTransaction','FinishedTransaction','Transacci�n Finalizada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','Reoccuring transaction','PlannedTransaction','PlannedTransaction','Transacci�n Prevista');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','Einmalige Transaktion','FinishedTransaction','FinishedTransaction','Transacci�n Finalizada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','Wiederkehrende Transaktion','PlannedTransaction','PlannedTransaction','Transacci�n Prevista');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','lowerLimit','Lower Limit','Unteres Limit','l�mite inferior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','upperLimit','Upper Limit','Oberes Limit','l�mite superior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','plannedTransactions','Trend (recurring transactions)','Verlauf (wiederkehrende Transaktionen)','Tend�ncia (transacciones recurrentes)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney1','Trend (pocket money 1)','Verlauf (Taschengeld 1)','Tendencia (dinero de bolsillo 1)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney2','Trend (pocket money 2)','Verlauf (Taschengeld 2)','Tendencia (dinero de bolsillo 2)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','savingTarget','Saving target','Verlauf (Sparziel)','Objetivo de ahorro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','colSymbol','Symbol','K�rzel','S�mbolo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','colLongName','long name','Bezeichnung','nombre largo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','BackupCreate','Create','Sichern','Crear');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','BackupUpload','Upload','Einspielen','Subir');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','colparentTitle','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','colTitle','Sub category','Unterkategorie','Sub categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','colDescription','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','colOutsideCapital','Outside capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','colTitle','Title','Titel','T�tulo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','colBalance','Balance','Kontostand','Balance');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','colCurrency','Currency','W�hrung','Divisa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colTitle','Title','Titel','T�tulo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colType','Type','Typ','Tipo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colDescription','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colValutaDate','Valuta date','Datum','Fecha');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colAmount','Amount','Betrag','Importe');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colCategoryTitle','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','title','About BADGER finance','�ber BADGER finance','Acerca de Badger');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','from','from','von','desde');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','published','Published under','Ver�ffentlicht unter','Publicado en');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','members','The members of the BADGER-Developer-Team.','Die Mitglieder des BADGER-Entwicklungs-Teams.','Los miembros del Equipo de Desarrolladores de BADGER');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','team','Developer-Team','Entwicklungs-Team','Equipo de Desarrolladores');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','programms','Used programms and components','Verwendete Programme und Komponenten','Programas y componentes usados');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','by','by','von','por');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','selectToolTip','Checked transactions will be imported.','Markierte Transaktionen werden importiert.','Transacciones que se importar�n comprobadas,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','categoryToolTip','Please choose a category .','W�hlen sie bitte eine Kategorie.','Por favor elija una categor�a.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','valuedateToolTip','Please enter the posting date.','Bitte geben sie das Buchungsdatum ein.','Por favor, introduzca la fecha de publicaci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','titleToolTip','Please enter the reason for transfer.','Bitte geben sie den Verwendungszweck der Transaktion ein.','Por favor, introduzca el motivo de la transferencia.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','amountToolTip','Please insert the amount of the transaction.','Bitte geben sie den Wert der Transaktion ein.','Por favor, introduzca el importe de la operaci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','transactionPartnerToolTip','Please enter the partner of the transaction.','Bitte geben sie den Transaktionspartner ein.','Por favor, introduzca el socio de la transacci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','descriptionToolTip','Please enter a description.','Bitte geben sie eine Beschreibung ein.','Por favor, introduzca una descripci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','periodicalToolTip','This setting is used for automatic pocket money calculation. When calculating your pocket money from the past (i.e. your regular money spending habits), the BADGER will ignore all transactions marked &quot;periodical&quot; because it assumes that you have those already covered in the future recurring transactions. An example would be your rent. For the future rent, you have entered a recurring transactions. Past rent payments are flagged &quot;periodical transactions&quot; and not used for pocket money calculation.','Diese Wert wird bei der automatischen Taschengeldberechnung benutzt. Wenn der BADGER das Taschengeld der Vergangenheit (also Ihr Ausgabeverhalten) berechnet, ignoriert er periodische Transaktionen, da angenommen wird, dass diese �ber wiederkehrende Transaktionen in der Zukunft bereits erfasst sind. Ein Beispiel hierf�r ist die Miete: F�r die Zukunft wird die Miete �ber eine wiederkehrende Transaktion abgebildet, muss also nicht im Taschengeld ber�cksichtigt werden. In der Vergangenheit sind die Mietzahlungen periodische Transaktionen.','Esta configuraci�n se utiliza para el c�lculo autom�tico de dinero de bolsillo. Al calcular su dinero de bolsillo del pasado (es decir, los h�bitos regulares de gasto de dinero), BADGER ignorar� todas las transacciones peri�dicas marcadas, ya que asume que se tienen abarcadas en el futuro como transacciones recurrentes. Un ejemplo de ello ser�a el alquiler. Para el alquiler futuro, usted ha entrado en una transacciones frecuente. Los pagos pasados de alquiler se marcan como \"operaciones peri�dicas\" y se utilizan para el c�lculo de dinero de bolsillo.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','ExceptionalToolTip','This setting is used for automatic pocket money calculation. When calculating your pocket money from the past (i.e. your regular money spending habits), the BADGER will ignore all transactions marked &quot;exceptional&quot; because they do not resemble your usual spending habits. Examples would be a surprise car repair job, a new tv (unless you buy new tvs every month) or a holiday.','Diese Wert wird bei der automatischen Taschengeldberechnung benutzt. Wenn der BADGER das Taschengeld der Vergangenheit (also Ihr Ausgabeverhalten) berechnet, ignoriert er au�ergew�hnliche Transaktionen. Beispiele hierf�r sind eine gro�e Autoreparatur, ein neuer Fernseher (wenn man nicht jeden Monat einen neuen kauft) oder ein Urlaub.','Esta configuraci�n se utiliza para el c�lculo autom�tico del dinero de bolsillo. Al calcular su dinero de bolsillo del pasado (es decir, sus h�bitos regulares de gasto de dinero), BADGER ignorar� todas las transacciones marcadas como excepcional porque no se parecen a sus h�bitos de gastos habituales. Un ejemplo ser�a una reparaci�n del autom�vil, una nueva televisi�n (a menos que compre nuevos televisores cada mes) o un d�a feriado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','outsideCapitalToolTip','If checked the amount of the transaction will be handled as outside capital, not as revenue. This are planned to be used for statistics and a balance sheet module in upcoming badger reaeses','Wenn die Checkbox markiert ist, wird der Wert der Transaktion als Fremdkapital behandelt, nicht als Einnahme. Dies soll in sp�teren Badgerversionen f�r Statistiken und eine Bilanz benutzt werden.','Si se selecciona el importe de la operaci�n ser� tratada como capital externo, no como ingresos. Esto est� previsto que se utilizar� para la estad�stica y un balance general del m�dulo en las pr�ximas versiones de BADGER');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','accountToolTip','Please choose a an account for the specific transaction.','Bitte w�hlen sie ein Konto f�r die einzelnen Transaktionen.','Por favor, elija una cuenta espec�fica para la transacci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','endDateField','End date','Enddatum','Fecha de finalizaci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','endDateToolTip','The forecast will be created from today to the selected date. The possible time span depends on your computer, the faster it is, the longer the time span can be. 1 year should be available on every computer.','Die Prognose wird vom heutigen Tag bis zu dem hier angegeben Tag erstellt. Der m�gliche Zeitraum h�ngt von Ihrem Rechner ab, je schneller der Rechner, desto l�nger kann er sein. 1 Jahr sollte aber auf jedem Rechner m�glich sein.','El pron�stico se crear� a partir de hoy hasta la fecha seleccionada. El posible lapso de tiempo, depende de su ordenador, el m�s r�pido es, puede ser cuanto m�s largo sea el per�odo de tiempo. 1 a�o deber�a estar disponible en cualquier equipo.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','accountField','Account','Konto','Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','accountToolTip','Please select the account for the forecast.','Bitte w�hlen Sie das Konto f�r die Prognose aus.','Por favor, seleccione la cuenta para el pron�stico.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','pageTitleOverview','Currency Manager','W�hrungs�bersicht','Administrador de divisas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','savingTargetField','Saving target','Sparziel','Objetivo de ahorro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','savingTargetToolTip','Please insert your saving target. When the forecast is created, there will be a graph where the balance at the end date reaches the saving target. Furthermore the pocketmoney will be shown, which is available for daily use under the condition, that the saving target has to be reached.','Bitte geben Sie Ihr Sparziel ein. Bei der Prognose wird ein Graph ausgegeben, bei dem am Enddatum dieser Kontostand erreicht wird. Au�erdem wird der Betrag ausgegeben, der Ihnen t�glich zum Ausgeben zur Verf�gung steht.','Por favor, introduzca su objetivo de ahorro. Cuando el plan de previsiones se crea, habr� un gr�fico donde el saldo a la fecha final llega a la meta de ahorro. Por otra parte se mostrar� el dinero de bolsillo, que est� disponible para el uso diario, como condici�n de que el objetivo de ahorro tiene que ser alcanzado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','pageTitleOverview','Transaction Categories','Transaktionskategorien','Categor�as de transacciones');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','pageTitleOverview','Account Overview','Konten�bersicht','Cuentas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney1Field','Pocket money 1','Taschengeld 1','Dinero de bolsillo 1');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney1ToolTip','Here you can insert an amount, which you want to dispose of every day (=pocket money). If you insert here an amount, a graph will be displayed, which shows the trend of your balances under consideration of the pocket money. Furthermore the balance at the end of the forecast period is shown.','Hier k�nnen Sie einen Betrag, den sie t�glich zur Verf�gung haben m�chten (=Taschengeld). Wenn Sie hier einen Wert eingeben, wird ein Graph angezeigt, der den Verlauf des Kontostandes anzeigt, wenn Sie diesen Betrag t�glich ausgeben. Au�erdem wird angezeigt, wie in diesem Falle der Kontostand am Enddatum ist.','Aqu� puede ingresar una cantidad que quiera disponer todos los d�as (= dinero de bolsillo). Si ingresa aqu� una cantidad, un gr�fico mostrar�, la tendencia de los saldos en virtud de su examen de dinero de bolsillo. Por otra parte, se mostrar� el saldo al final del per�odo analizado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','pageTitle','Account properties','Kontoeigenschaften','Propiedades de la cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney2Field','Pocket money 2','Taschengeld 2','Dinero de bolsillo 2');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney2ToolTip','Here you can insert a second pocket money (see tool tip for pocket money 1). This creates another graph to get an comparision. The balanced at the end of the period will also been shown.','Hier k�nnen Sie ein weiteres Taschengeld angeben (siehe ToolTip zu Taschengeld 1). Dies erzeugt einen weiteren Graphen zum vergleichen. Der Endkontostand wird ebenfalls angezeigt.','Aqu� puede ingresar un segundo dinero de bolsillo (v�ase la herramienta para dinero de bolsillo 1). Esto crea otro gr�fico para obtener una comparaci�n. El balance al final del per�odo tambi�n se mostrar�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','lowerLimitLabel','Graph lower limit','Graph unteres Limit','Gr�fico l�mite inferior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','lowerLimitToolTip','Shows the lower limit in the graph.','Zeigt im Diagramm das untere Limit des Zielkontos an.','Muestra el l�mite inferior en el gr�fico.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','New','New','Neu','Nuevo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','NewAccount','New Account','Neues Konto','Nueva Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','NewCategory','New Category','Neue Transaktionskategorie','Nueva Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','upperLimitLabel','Graph upper limit','Graph oberes Limit','Gr�fico l�mite superior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','upperLimitToolTip','Shows the upper limit in the graph.','Zeigt im Diagramm das obere Limit des Zielkontos.','Muestra el l�mite superior en el gr�fico.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','pageTitle','Edit Currency','W�hrung bearbeiten','Editar Divisa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','pageTitleEdit','Edit Category','Kategorie bearbeiten','Editar Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','plannedTransactionsLabel','Graph planned transactions','Graph geplante Transaktionen','Gr�fico de operaciones previstas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','plannedTransactionsToolTip','Shows the graph for planned transactions. The saving target and pocket money will not be included.','Zeigt den Graph f�r die geplanten Transaktionen. Es wird kein Sparziel und kein Taschengeld ber�cksichtigt.','Muestra el gr�fico de las operaciones previstas. El objetivo de ahorro de dinero de bolsillo y no se incluir�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','pageTitle','Transaction overview','Transaktions�bersicht','Transacciones');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','savingTargetLabel','Graph saving target','Graph mit Sparziel','Gr�fico del objetivo de ahorro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','showSavingTargetToolTip','Shows the trend including the saving target.','Zeigt den Verlauf des Kontostandes unter Ber�cksichtigung des Sparzieles an.','Muestra la tendencia inclyendo el objetivo de ahorro.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','pageTitleProp','Edit Account','Konto bearbeiten','Editar Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney1Label','Graph pocket money 1','Graph Taschengeld 1','Gr�fico del dinero de bolsillo 1');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','showPocketMoney1ToolTip','Shows the trend of the account balance including the pocket money 1','Zeigt den Verlauf des Kontostandes unter Ber�cksichtigung des Taschengeldes 1.','Muestra la tendencia del balance de cuenta incluyendo el dinero de bolsillo 1');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','pocketMoney2Label','Graph pocket money 2','Graph Taschengeld 2','Gr�fico del dinero de bolsillo 2');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','showPocketMoney2ToolTip','Shows the trend of the account balance including the pocket money 2','Zeigt den Verlauf des Kontostandes unter Ber�cksichtigung des Taschengeldes 2','Muestra la tendencia del balance de cuenta incluyendo el dinero de bolsillo 2');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','noGraphchosen','No graph was chosen to display.','Kein Graph zum Anzeigen gew�hlt.','No se selecciom� un gr�fico para mostrar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','noLowerLimit','The selected account has no lower limit.','Das gew�hlte Konto hat kein unteres Limit.','La cuenta seleccionada no tiene l�mite inferior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','noUpperLimit','The selected account has no upper limit.','Das gew�hlte Konto hat kein oberes Limit.','La cuenta seleccionada no tiene l�mite superior');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','accColTitle','Title','Titel','T�tulo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','accColBalance','Balance','Kontostand','Balance');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','accColCurrency','Currency','W�hrung','Divisa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','onlyFutureDates','The enddate have to be in the future. For data from the past please use the statistics.','Das Enddatum muss in der Zukunft liegen. F�r Vergangenheitsdaten benutzen Sie bitte die Statistiken.','La fecha final tiene que ser hacia el futuro. Para los datos anteriores haga uso de las estad�sticas.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','pageTitle','Statistics','Statistik erstellen','Estad�sticas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','title','Title','Titel','T�tulo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','type','Type','Typ','Tipo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','category','Category','Kategorie-Art','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','period','Period','Zeitraum','Per�odo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','catMerge','Category merge','Kategorien zusammenfassen','Fusi�n de categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','accounts','Accounts','Konten','Cuentas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','attention','Attention: No currency conversion takes place during display of accounts with different currencies.','Achtung: Bei der gleichzeitigen Betrachtung mehrerer Konten mit unterschiedlichen W�hrungen findet keine Umrechnung statt!','Atenci�n: No se llevar� a cabo una conversi�n de moneda durante la exhibicion de cuentas con diferentes divisas.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','from','From','Vom','desde');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','to','to','bis','hasta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','beginDate','Begin date','Startdatum','Fecha de inicio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','endDate','End date','Enddatum','Fecha de finalizaci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','jan','January','Januar','enero');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','feb','February','Februar','febrero');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','repeatUnit','Repeat unit','Einheit','Unidad de repetici�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','repeatFrequency','Repeat frequency','Intervall','Frecuencia de repetici�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','dailyPocketMoneyLabel','Pocket money for reaching saving Target','Taschengeld um Sparziel zu erreichen','Dinero de bolsillo para lograr la meta de ahorro.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','dailyPocketMoneyToolTip','Money, that can be spent every day, if the saving target should be reached. If negative, this amount has to be to be earned every day.','Geld, das maximal t�glich zur Verf�gung steht, wenn das Sparziel erreicht werden soll. Wenn negativ, muss im Durchschnitt jeden Tag soviel Geld eingenommen werden.','El dinero, que puede ser gastado cada d�a, si el objetivo de ahorro es alcanzado. De lo contrario, esta cantidad tiene que ser obtenida cada d�a.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','mar','March','M�rz','marzo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','apr','April','April','abril');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','may','May','Mai','mayo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','jun','June','Juni','junio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','jul','July','Juli','julio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','aug','August','August','agosto');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','sep','September','September','septiembre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','oct','October','Oktober','octubre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','nov','November','November','noviembre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','dec','December','Dezember','diciembre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','printedPocketMoney1Label','Balance at the end date (pocket money 1','Kontostand am Enddatum (Taschengeld 1','Saldo a la fecha de finalizaci�n (dinero de bolsillo 1)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','printedPocketMoney2Label','Balance at the end date (pocket money 2','Kontostand am Enddatum (Taschengeld 2','Saldo a la fecha de finalizaci�n (dinero de bolsillo 2)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','income','Income','Einnahmen','Ingreso');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','expenses','Expenses','Ausgaben','Gastos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','subCat','Merge sub-categories with main-categories','Unterkategorien unter der Hauptkategorie zusammenfassen','Fusionar sub-categor�as con categor�as principales');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','subCat2','Show sub-catagory individually','Unterkategorien eigenst�ndig auff�hren','Mostrar sub-categor�as individualmente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','errorMissingAcc','You did not choose an account.','Sie haben noch kein Konto ausgew�hlt.','No ha seleccionado una cuenta.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','errorDate','Start date before end date.','Das Startdatum liegt nicht vor dem Enddatum.','La fecha de inicio antes de la fecha de finalizaci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','errorEndDate','End date in the future.','Das Enddatum liegt in der Zukunft.','La fecha de finalizaci�n hacia el futuro.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','legendSetting','Parameter','Parameter','Par�metro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','legendGraphs','Select graphs','Graphen ausw�hlen','Seleccionar gr�ficos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','pageTitle','Transaction','Transaktion','Transacci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','title','User Settings','Einstellungen','Configuraci�n de usuario');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','title','Forecast','Prognose','Pron�stico');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('about','contributors','Contributors','Mitwirkende','Colaboradores');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','legend','Properties','Eigenschaften','Propiedades');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('askInsert','legend','Import','Import','Importar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('askExport','legend','Export','Export','Exportar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','newPlannedTrans','New recurring transaction','Neue wiederkehrende Transaktion','Nueva transacci�n frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','newFinishedTrans','New single transaction','Neue einmalige Transaktion','Nueva transacci�n simple');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','no_parent','&lt;No parent category&gt;','&lt;Keine Elternkategorie&gt;','&lt;Sin categor�a padre&gt;');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','legend','Properties','Eigenschaften','Propiedades');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','legend','Properties','Eigenschaften','Propiedades');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','legend','Properties','Eigenschaften','Propiedades');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','NewTransactionFinished','New Transaction (single)','Neue Transaktion (einmalig)','Nueva Transacci�n (simple)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','NewTransactionPlanned','New Transaction (recurring)','Neue Transaktion (wiederkehrend)','Nueva Transacci�n (frecuente)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','headingTransactionFinished','Single transaction','Einmalige Transaktion','Transacci�n simple');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','headingTransactionPlanned','Recurring transaction','Wiederkehrende Transaktion','Transacci�n Frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','Account','Account','Konto','Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','calculatedPocketMoneyLabel','Automatically calculate pocket money 2','Taschengeld 2 automatisch berechnen','Calcular autom�ticamente dinero de bolsillo 2');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','calculatedPocketMoneyToolTip','If you press this button, a pocket money will be generated automatically and written to the pocket money 2 field. For the calculation every transaction between the selected date & today will be used, which are not marked as exceptional or periodical.','Wenn Sie den Button dr�cken, wird automatisch aus der Datenbank ein Taschengeld generiert und in das Feld Taschengeld 2 geschrieben. Beim berechnen werden alle Transaktionen ber�cksichtigt, die zwischen dem hier angew�hltem Datum und heute liegen, und nicht als regelm��ig oder au�ergew�hnlich markiert sind.','Si pulsa este bot�n, un dinero de bolsillo se generar� autom�ticamente y por escrito el dinero de bolsillo 2. Para el c�lculo cada transacci�n entre la fecha seleccionada y hoy, se utilizar�n las que est�n marcadas como excepcionales o peri�dicos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colSum','Sum','Summe','Suma');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','calculatedPocketMoneyButton','Calculate','Berechnen','Calcular');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','noCategoryAssigned','(not assigned)','(nicht zugeordnet)','(no asignado)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger','PrintMessage','Print','Drucken','Imprimir');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('forecast','performanceWarning','Please pay attention to this fact before pressing the button: If the time span between today and the end date is too long, a message from the macromedia flash player appears. In this case please reduce the time span for the forecast. During the test on different computers a forecast between 1 up to 4 years were possible.','Bitte beachten Sie: Je weiter das Enddatum in der Zukunft liegt, desto l�nger dauert das Erstellen des Diagrammes. Wenn es zu weit in der Zukunft liegt, kann es zu einer Meldung des Macromedia Flash Players kommen. Verk�rzen Sie in diesem Fall die Prognosedauer. Je nach Testrechner waren Prognosen zwischen 1 und 4 Jahren m�glich.','Por favor, preste atenci�n a esto antes de pulsar el bot�n: Si el lapso de tiempo entre hoy y la fecha final es demasiado largo, un mensaje de Macromedia Flash Player aparecer�. En este caso, por favor reducir el lapso de tiempo para el pron�stico. Durante el ensayo en diferentes ordenadores un plan de previsiones de entre 1 a 4 a�os fueron posibles.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','SQLError','An SQL error occured attempting to fetch the navigation data from the database.','Beim Abrufen der Navigations-Daten aus der Datenbank trat ein SQL-Fehler auf.','Se ha producido un error SQL al intentar obtener la informaci�n de navegaci�n de la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','UnknownNavigationId','An unknown id of an navigation entry was used.','Es wurde eine unbekannte ID eines Navigationseintrags benutzt.','Se us� una ID desconocida de una entrada de navegaci�n,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','trend','Trend','Trend','Tendencia');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','categories','Categories','Kategorien','Categor�as');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','noAccountID','noAccountID','es wurde keine AccountID �bermittelt','Sin AccountID');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','pageTitle','Recurring transaction overview','�bersicht wiederkehrender Transaktionen','Transacciones Frecuentes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','colBeginDate','Begin Date','Startdatum','Fecha de inicio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','colEndDate','End date','Enddatum','Fecha de finalizaci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','colUnit','Unit','Einheit','Unidad');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','colFrequency','Interval','Intervall','Intervalo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','edit','Edit','Bearbeiten','Editar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','NoRowSelectedMsg','Please, select a row to edit','Bitte selektieren sie eine Zeile, die sie bearbeiten wollen.','Por favor, elija una fila para editar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('jsVal','err_form','Please enter/select values for the following fields: ','Bitte geben Sie die Werte f�r folgende Felder ein: ','Por favor, introduzca/seleccione valores de los siguientes campos: ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('jsVal','err_select','Please select a valid \"%FIELDNAME%\"','Bitte w�hlen Sie einen g�ltigen Wert f�r \"%FIELDNAME%\"','Por favor, seleccione un \"%FIELDNAME%\" v�lido');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('jsVal','err_enter','Please enter a valid \"%FIELDNAME%\"','Bitte geben Sie einen g�ltigen Wert f�r \"%FIELDNAME%\" ein','Por favor, ingrese un \"%FIELDNAME%\" v�lido');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCurrency','currencyIsStillUsed','The Currency is still used. You cannot delete it.','Die W�hrung wird noch verwendet und kann daher nicht gel�scht werden.','La divisa est� en uso. No se puede eliminar.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','deleteMsg','Do you really want to delete the selected categories? Note: All transactions using the selected categories will lose their categorization information and become uncategorized transactions.','Wollen sie die selektierten Kategorien wirklich l�schen?\nHinweis: Von allen Transaktionen, die diese Kategorie(n) verwenden, wird die Kategorie zur�ckgesetzt.','�Est� seguro de que desea borrar las categor�as seleccionadas? Nota: Todas las transacciones que usan las categor�as seleccionadas perder�n su categor�a y ser�n transacciones sin categor�a.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','deleteMsg','Do you really want to delete the selected accounts with all transactions?','Wollen sie die selektierten Konten wirklich mit allen Transaktionen l�schen?','�Est� seguro de que desea eliminar las cuentas seleccionadas con todas sus transacciones?');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','backend_not_login','Error: You do not have permission to access this page.','Fehler: Sie haben keine Berechtigung, auf diese Seite zuzugreifen.','Error: No tiene permiso para acceder a esta p�gina.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','outsideCapital','Outside Capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('CategoryManager','ownCapital','Own Capital','Eigenkapital','Capital propio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','legend','Legend','Legende','Leyenda');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','autoExpandPlannedTransactionsName','Auto-insert recurring transactions','Wiederkehrende Transaktionen automatisch eintragen','Auto-inserci�n de transacciones frecuentes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','autoExpandPlannedTransactionsDescription','If this option is checked, every occuring instance of a recurring transaction is automatically inserted as an single transaction. Uncheck this if you import your transactions from a CSV file on a regular basis.','Wenn diese Option ausgew�hlt wurde, werden eintretende Instanzen einer wiederkehrenden Transaktion automatisch als einmalige Transaktionen eingetragen. W�hlen Sie die Option nicht aus, wenn Sie Ihre Transaktionen regelm��ig aus einer CSV-Datei importieren.','Si esta opci�n est� marcada, cada instancia de una transacci�n recurrente se inserta de forma autom�tica como una transacci�n simple. Desmarque esto si importa sus transacciones desde un archivo CSV de forma regular.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','showPlannedTrans','Show recurring transactions','Wiederkehrende Transaktionen anzeigen','Mostrar transacciones frecuentes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','showTrans','Show all transactions','Alle Transaktionen anzeigen','Mostrar todas las transacciones');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','futureCalcSpanLabel','Planning horizon (months)','Planungszeitraum in Monaten','Horizonte previsto (meses)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','futureCalcSpanDescription','Please enter how far into the future you would like to be able to plan. With usability in mind, recurring transactions will only be displayed as far into the future as you enter here.','Geben Sie hier ein, wie weit Sie in die Zukunft planen m�chten. Wiedekehrende Transaktionen werden der �bersichtlichkeit wegen nur so weit in die Zukunft dargestellt, wie Sie hier eingeben.','Por favor, introduzca la medida de tiempo hacia el futuro que le gustar�a planificar. Con la usabilidad en mente, las transacciones recurrentes s�lo se mostrar�n en la medida de tiempo hacia el futuro introducidas aqu�.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','trendTotal','Total','Gesamt','Total');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','pageTitlePropNew','New Account','Konto erstellen','Nueva Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('badger_login','sessionTimeout','Your session timed out. You have been logged out for security reasons.','Ihre Sitzung ist abgelaufen. Sie wurden aus Sicherheitsgr�nden ausgeloggt.','Su sesi�n ha caducado. Se ha cerrado la sesi�n por razones de seguridad.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','step1PostLink','','','');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','step2PreLink','Please click the following link to start the database update.','Bitte klicken Sie auf folgenden Link, um die Datenbank-Aktualisierung zu beginnen.','Por favor, haga clic en el siguiente enlace para iniciar la actualizaci�n de la  base de datos de.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','step1PreLink','Please click the following link and save the file to your computer.','Bitte klicken Sie auf folgenden Link und speichern Sie die Datei auf Ihrem Computer.','Por favor, haga clic en el siguiente enlace y guarde el archivo en su ordenador.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','step1LinkText','Save backup','Sicherungskopie speichern','Guardar copia de seguridad,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','fileVersionText','File version:','Datei-Version:','Versi�n de archivo:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','stepDescription','The update consists of two simple steps. First, a backup of the database is saved to your computer. This preserves your data in the rare case anything goes wrong. Second, the database is updated.','Die Aktualisierung besteht aus zwei einfachen Schritten. Zuerst wird eine Sicherheitskopie der Datenbank auf Ihrem Computer gespeichert. Dadurch bleiben Ihre Daten auch im unwahrscheinlichen Fall eines Fehlschlags erhalten. Anschlie�end wird die Datenbank aktualisiert.','La actualizaci�n consta de dos simples pasos. En primer lugar, una copia de seguridad de la base de datos es guardada en su ordenador. Este conserva sus datos en el caso raro que algo salga mal. En segundo lugar, la base de datos se actualiza.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','dbVersionText','Database version:','Datenbank-Version:','Versi�n de la base de datos:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','legend','Steps to Update','Schritte zur Aktualisierung','Pasos para actualizar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','updateInformation','BADGER finance detected an update of its files. This page updates the database. All your data will be preserved.','BADGER finance hat eine Aktualisierung seiner Dateien festgestellt. Diese Seite aktualisiert die Datenbank. Ihre Daten bleiben vollst�ndig erhalten.','BADGER ha detectado una actualizaci�n de sus archivos. Esta p�gina actualiza la base de datos. Todos sus datos ser�n conservados.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','pageTitle','Update BADGER finance','BADGER finance aktualisieren','Actualizar BADGER');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','step2LinkText','Update database','Datenbank aktualisieren','Actualizar la base de datos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateProcedure','step2PostLink','','','');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','pageTitle','Updating BADGER finance','BADGER finance wird aktualisiert','Actualizando BADGER');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','betweenVersionsText','Versions in between:','Dazwischenliegende Versionen:','Entre las versiones:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','preCurrentText','Update from','Aktualisierung von','Actualizado desde');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','postCurrentText','to','auf','hasta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','postNextText','','','');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','logEntryHeader','Information from the update:','Informationen der Aktualisierung:','Informaci�n de la actualizaci�n:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','updateInformation','BADGER finance is now performing the update. It is performed step-by-step, one step for each version.','Die Aktualisierung wird nun durchgef�hrt. Dies findet Schritt f�r Schritt statt, einen Schritt f�r jede Version.','BADGER est� realizando la actualizaci�n. Se realiza paso a paso, un paso para cada versi�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','errorInformation','Please read the output of the process. If it encounters any severe errors they are written in red. In this case, please send the whole output to the BADGER development team (see help for contact info).','Bitte lesen sie die Ausgabe dieses Prozesses. Die einfachen Informationen sind auf Englisch gehalten. Falls der Prozess irgend welche schweren Fehler meldet, sind diese rot eingef�rbt. Bitte schicken Sie in diesem Fall die gesamte Ausgabe an das BADGER Entwicklungsteam (siehe Hilfe f�r Kontaktinformationen).','Please read the output of the process. If it encounters any severe errors they are written in red. In this case, please send the whole output to the BADGER development team (see help for contact info).');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','updateFinished','The update has finished.','Die Aktualisierung ist beendet.','La actualizaci�n ha finalizado.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','severeError','The update encountered a severe error. Please send the whole output to the BADGER finance development team.','Die Aktualisierung stie� auf einen schweren Fehler. Bitte schicken Sie die gesamte Ausgabe an das BADGER finance development team.','Por favor, lea la informaci�n del proceso. Si encuentra alg�n error escrito en rojo, por favor enviar toda la informaci�n al equipo de desarrollo BADGER (ir a ayuda para informaci�n de contacto).');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','goToStartPagePreLink','Please','Bitte','Por favor');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','goToStartPageLinkText','go to start page','zur Startseite gehen','ir a la p�gina de inicio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('updateUpdate','goToStartPagePostLink','to continue.','um fortzusetzen.','para continuar.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','goToStartPagePreLink','Please','Bitte','Por favor');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','goToStartPageLinkText','go to start page','zur Startseite gehen','ir a la p�gina de inicio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','goToStartPagePostLink','to continue.','um fortzusetzen.','para continuar.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importExport','newerVersion','Your backup file was from a previous version of BADGER finance. A database update will occur.','Ihre Sicherheitskopie war von einer vorherigen Version von BADGER finance. Es wird eine Datenbank-Aktualisierung stattfinden.','Su archivo de copia de seguridad es de una versi�n anterior de BADGER. Se realizar� una actualizaci�n de la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DateFormats','mm/dd/yy','mm/dd/yy','mm/tt/jj','mm/dd/aa');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics','showButton','Show','Anzeigen','Mostrar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','open','Open','�ffnen','Abrir');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','releaseNotes','Release Notes','Versionsgeschichte (englisch)','Notas de la versi�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('welcome','pageTitle','Your accounts','Ihre Konten','Sus cuentas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','filterLegend','Filter','Filter','Filtro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','setFilter','Set Filter','Filtern','Establecer filtro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','resetFilter','Reset','Reset','Reestablecer');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('common','gpcFieldUndefined','GET/POST/COOKIE field undefined','GET/POST/COOKIE-Feld nicht definiert','Campo GET/POST/COOKIE indefinido');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','pageTitleNew','Create new Catagory','Neue Kategorie erstellen','Crear nueva Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('DataGridHandler','illegalFieldSelected','The following field is not known to this DataGridHandler:','Das folgende Feld ist diesem DataGridHandler nicht bekannt:','El siguiente campo no es reconocido por DataGridHandler:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('MultipleAccounts','invalidFieldName','An unknown field was used with MultipleAccounts.','Es wurde ein unbekanntes Feld mit MultipleAccounts verwendet.','Fue usado un campo desconocido con MultipleAccounts.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','deleteOldPlannedTransactions','Auto-insert recurring transactions:','Wiederkehrende Transaktionen automatisch eintragen:','Auto-insertar transacciones frecuentes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','csvParser','CSV parser:','CSV-Parser:','Analizador CSV:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','deleteOldPlannedTransactionsDescription','If this option is checked, every occuring instance of a recurring transaction is automatically inserted as an single transaction. Uncheck this if you import your transactions from a CSV file on a regular basis.','Wenn diese Option ausgew�hlt wurde, werden eintretende Instanzen einer wiederkehrenden Transaktion automatisch als einmalige Transaktionen eingetragen. W�hlen Sie die Option nicht aus, wenn Sie Ihre Transaktionen regelm��ig aus einer CSV-Datei importieren.','Si esta opci�n est� marcada, cada instancia de una transacci�n recurrente se inserta de forma autom�tica como una transacci�n simple. Desmarque esto si importa sus transacciones desde un archivo CSV de forma regular.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','range','Apply to','Anwenden auf','Aplicar a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','rangeAll','all','alle','todo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','rangeThis','this','diese','esta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','rangePrevious','this and previous','diese und vorherige','esta y anteriores');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','rangeFollowing','this and following','diese und folgende','esta y posteriores');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','rangeUnit','instances','Auspr�gungen','instancias');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('plannedTransaction','afterTitle','after','nach','despues');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('plannedTransaction','beforeTitle','before','vor','antes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('AccountManager','UnknownFinishedTransactionId','An unknown single transaction id was used.','Es wurde eine unbekannte ID einer einmaligen Transaktion verwendet.','Fue usado un ID desconocido de una transacci�n simple');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('AccountManager','UnknownPlannedTransactionId','An unknown recurring transaction id was used.','Es wurde eine unbekannte ID einer wiederkehrenden Transaktion verwendet.','Fue usado un ID desconocido de una transacci�n frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','transferalEnabled','Add transferal transaction','Gegenbuchung hinzuf�gen','A�adir transacci�n de transferencia');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','transferalAccount','Target account','Zielkonto','Cuenta destino');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','transferalAmount','Amount on target Account','Betrag auf Zielkonto','Importe en Cuenta destino');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','FinishedTransferalSourceTransaction','Source of single transferal transaction','Quelle einer Einmaligen Gegenbuchung','Origen de transacci�n de transferencia simple');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','FinishedTransferalTargetTransaction','Target of single transferal transaction','Ziel einer Einmaligen Gegenbuchung','Destino de transacci�n de transferencia simple');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','PlannedTransferalSourceTransaction','Source of recurring transferal transaction','Quelle einer Wiederkehrenden Gegenbuchung','Origen de transacci�n de transferencia frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','PlannedTransferalTargetTransaction','Target of recurring transferal transaction','Ziel einer Wiederkehrenden Gegenbuchung','Destino de transacci�n de transferencia frecuente');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCommon','includeSubCategories','(including sub-categories)','(Unterkategorien eingeschlossen)','(incluyendo sub-categor�as)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('widgetEngine','noImage','An image file cannot be found in the current theme or the Standard theme.','Eine Bilddatei kann weder im aktuellen noch im Standardtheme gefunden werden.','Un archivo de imagen no se puede encontrar en el tema actual o el tema est�ndar.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('NavigationFromDB','noIcon','An navigation icon cannot be found in the current theme or the Standard theme.','Ein Navigationsicon kann weder im aktuellen noch im Standardtheme gefunden werden.','Un icono de navegaci�n no se puede encontrar en el tema actual o en el tema est�ndar.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','keywordsLabel','Keywords','Schl�sselw�rter','Palabras clave');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','keywordsDescription','If an imported transaction contains one of these keywords, this category will be pre-selected for this transaction. Use one line per keyword.','Wenn eine importierte Transaktion eines dieser Schl�sselw�rter enth�lt, wird diese Kategorie vor-ausgew�hlt. Geben Sie pro Schl�sselwort eine neue Zeile ein.','Si una transacci�n importada contiene una de estas palabras clave, esta categor�a se pre-seleccionar� para esta transacci�n. Use una l�nea por palabra clave.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingDateDeltaLabel','Max. difference in days:','Max. Differenz in Tagen','M�axima diferencia en d�as:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingDateDeltaDescription','Only transactions that differ at most this amount of days from the imported transaction are considered for comparison.','Nur Transaktionen, die maximal diese Anzahl an Tagen von der importierten Transaktion abweichen, werden zum Vergleich herangezogen.','S�lo las transacciones que difieran en la mayor�a de estos d�as se considerar�n para la comparaci�n con la transacci�n importada.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingAmountDeltaLabel','Max. difference of amount (%)','Max. Abweichung des Betrags (%)','M�xima diferencia de importe (%)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingAmountDeltaDescription','Only transactions that differ at most this percentage in amount from the imported transaction are considered for comparison.','Nur Transaktionen, deren Betrag maximal diesen Prozentsatz von der importierten Transaktion abweichen, werden zum Vergleich herangezogen.','S�lo las transacciones que difieran en la mayor�a de este porcentaje se considerar�n para la comparaci�n con transacci�n importada.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingTextSimilarityLabel','Min. text similarity (%)','Mind. Text�hnlichkeit (%)','M�nima similitud de texto (%)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingTextSimilarityDescription','Only transactions that are similar to the imported transaction by this percentage are considered for comparison.','Nur Transaktionen, die mindestens diesen Prozentsatz an �hnlichkeit zur importierten Transaktion aufweisen, werden zum Vergleich herangezogen.','S�lo las transacciones que son similares en este porcentaje a transacci�n importada considerar�n para la comparaci�n.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('UserSettingsAdmin','matchingHeading','CSV Import Matching','Abgleich beim CSV-Import','Importaci�n CSV Concordante');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','matchingHeader','Similar Transactions','�hnliche Transaktionen','Transacciones Similares');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','matchingToolTip','If you choose a transaction here, it will be replaced by the imported data.','Wenn Sie hier eine Transaktion ausw�hlen, wird sie durch die importierten Daten ersetzt.','Si elige una transacci�n aqu�, ser� sustituida por los datos importados.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','dontMatchTransaction','&lt;Import as new&gt;','&lt;Neu importieren&gt;','&lt;Importar como nueva&gt;');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','descriptionFieldImportedPartner','Imported transaction partner:','Importierter Transaktionspartner:','Transacciones asociadas importadas:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','descriptionFieldOrigValutaDate','Original valuta date:','Original-Buchungsdatum:','Fecha original:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','descriptionFieldOrigAmount','Original amount:','Original-Betrag:','Importe original:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverview','colBalance','Balance','Kontostand','Saldo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','colAccountName','Account','Konto','Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','pageTitle','Advanced Statistics','Erweiterte Statistik','Estad�sticas Avanzadas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','titleFilter','Title is','Titel ist','El T�tulo es ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','descriptionFilter','Description is','Beschreibung ist','La Descripci�n es ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','valutaDateFilter','Valuta date is','Buchungsdatum ist','La Fecha es ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','valutaDateBetweenFilter','Valuta date is between','Buchungsdatum ist zwischen','Fecha entre ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','valutaDateBetweenFilterConj','and','und',' y ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','valutaDateBetweenFilterInclusive','(both inclusive)','(beide inklusive)',' (ambas inclusive) ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','valutaDateAgoFilter','Valuta within the last','Buchungsdatum innerhalb der letzten','Valor en los �ltimos ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','valutaDateAgoFilterDaysAgo','days','Tage',' d�as ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','amountFilter','Amount is','Betrag ist','El importe es ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outsideCapitalFilter','Source is','Quelle ist','El origen es ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outsideCapitalFilterOutside','outside capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outsideCapitalFilterInside','inside capital','Eigenkapital','capital interno');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','transactionPartnerFilter','Transaction partner is','Transaktionspartner ist','La transacci�n asociada es ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','categoryFilter','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','categoryFilterIs','is','ist','is');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','categoryFilterIsNot','is not','ist nicht','no es');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','exceptionalFilter','Transaction is','Transaktion ist','La transacci�n es');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','exceptionalFilterExceptional','exceptional','au�ergew�hnlich','excepcional');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','exceptionalFilterNotExceptional','not exceptional','nicht au�ergew�hnlich','no es excepcional');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','periodicalFilter','Transaction is','Transaktion ist','La transacci�n es');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','periodicalFilterPeriodical','periodical','regelm��ig','peri�dica');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','periodicalFilterNotPeriodical','not periodical','unregelm��ig','no es peri�dica');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersUnselected','Please choose a filter','Bitte w�hlen Sie einen Filter','Por favor, elija un filtro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersTitle','Title','Titel','T�tulo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersDescription','Description','Beschreibung','Descripci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersValutaDate','Valuta date','Buchungsdatum','Fecha');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersValutaDateBetween','Valuta date between','Buchungsdatum zwischen','Fecha entre ');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersValutaDateAgo','Valuta date last days','Buchungsdatum vergangene Tage','Fecha �ltimos d�as');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersAmount','Amount','Betrag','Importe');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersOutsideCapital','Outside capital','Fremdkapital','Capital externo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersTransactionPartner','Transaction partner','Transaktionspartner','Transacci�n asociada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersCategory','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersExceptional','Exceptional','Au�ergew�hnlich','excepcional');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersPeriodical','Periodical','Regelm��ig','peri�dica');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','availableFiltersDelete','&lt;Delete Filter&gt;','&lt;Filter l�schen&gt;','&lt;Borrar Filtro&gt;');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','filterCaption','Filters','Filter','Filtros');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','twistieCaptionInput','Input Values','Eingabewerte','Valores de Entrada');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTrendStartValue','Start Value','Startwert','Valores de Inicio');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTrendStartValueZero','0 (zero)','0 (null)','0 (cero)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTrendStartValueBalance','Account Balance','Kontostand','Balance de la Cuenta');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTrendTickLabels','Tick labels','Tickmarken','Marcar etiquetas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTrendTickLabelsShow','Show','Anzeigen','Mostrar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTrendTickLabelsHide','Hide','Verbergen','Ocultar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionCategoryType','Category Type','Kategorietyp','Tipo de Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionCategoryTypeInput','Income','Einnahmen','Ingresos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionCategoryTypeOutput','Spending','Ausgaben','Gastos');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionCategorySubCategories','Sub-Categories','Unterkategorien','Sub-Categor�as');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionCategorySubCategoriesSummarize','Summarize sub-categories','Unterkategorien zusammenfassen','Resumir sub-categor�as');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionCategorySubCategoriesNoSummarize','Do not summarize sub-categories','Unterkategorien einzeln auff�hren','No resumir sub-categor�as');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTimespanType','Type','Typ','Tipo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTimespanTypeWeek','Week','Woche','semana');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTimespanTypeMonth','Month','Monat','mes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTimespanTypeQuarter','Quarter','Quartal','Trimestre');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionTimespanTypeYear','Year','Jahr','A�o');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionGraphType','Graph Type','Graphtyp','Tipo de Gr�fico');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionGraphTypeTrend','Trend','Verlauf','Tendencia');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionGraphTypeCategory','Category','Kategorie','Categor�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','outputSelectionGraphTypeTimespan','Timespan','Zeitvergleich','Per�odo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','twistieCaptionOutputSelection','Output Selection','Ausgabeauswahl','Resultado de la selecci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','analyzeButton','Analyse','Analysieren','Analizar');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','twistieCaptionGraph','Graph','Graph','Gr�fico');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','twistieCaptionOutput','Output','Ausgabe','Resultado');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','addFilterButton','Add Filter','Filter hinzuf�gen','A�adir Filtro');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2Graph','noMatchingTransactions','No transactions match your criteria.','Keine Transaktionen entsprechen Ihren Kriterien.','No hay operaciones que coinciden con sus criterios.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','beginsWith','begins with','f�ngt an mit','comienzan con');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','endsWith','ends with','h�rt auf mit','finalizan con');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','contains','contains','enth�lt','contienen');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','dateEqualTo','equal to','gleich','igual a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','dateBefore','before','vor','antes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','dateBeforeEqual','before or equal to','vor oder gleich','antes o igual a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','dateAfter','after','nach','despues');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','dateAfterEqual','after or equal to','nach oder gleich','despues o igual a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','dateNotEqual','not equal to','ungleich','diferente a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Navigation','Statistics2','Advanced Statistics','Erweiterte Statistik','Estad�sticas Avanzadas');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountAccount','csvNoParser','&lt;No parser&gt;','&lt;Kein Parser&gt;','&lt;No analizar&gt;');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('PageSettings','SQLError','An SQL error occured attempting to fetch the PageSettings data from the database.','Beim Abrufen der PageSettings-Daten aus der Datenbank trat ein SQL-Fehler auf.','Se ha producido un error SQL al intentar obtener el PageSettings de la base de datos.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','pageSettingSave','Save Settings','Einstellungen speichern','Guardar Configuraci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','pageSettingDelete','Delete Setting','Einstellung l�schen','Eliminar Configuraci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','pageSettingsTwistieTitle','Settings','Einstellungen','Configuraci�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','pageSettingNewNamePrompt','Please enter the name for the setting:','Bitte geben Sie den Namen f�r die Einstellung ein:','Por favor, introduzca el nombre para la configuraci�n:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','expenseRowLabel','Standard direction:','Standardgeldfluss:','Direcci�n est�ndar:');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','expenseIncome','Income','Einnahme','Ingreso');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountCategory','expenseExpense','Expense','Ausgabe','Gasto');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountTransaction','categoryExpenseWarning','The selected category is marked as expense, but your amount is positive.','Die ausgew�hlte Kategorie ist als Ausgabe markiert, jedoch ist Ihr Betrag positiv.','La categor�a seleccionada est� marcada como gasto, pero su monto es positivo,');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2','miscCategories','(Miscellaneous)','(Verbleibende)','(Varios)');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGrid','back','Back','Zur�ck','Volver');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','uploadTitle','File Uploaded and Analyzed','Datei hochgeladen und analysiert','Archivo subido y analizado');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','submitTitle','CSV Data Imported','CSV-Daten importiert','Datos CSV Importados');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('importCsv','pageHeading','CSV Import','CSV-Import','Importar CSV');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','textday','day','Tag','d�a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','textmonth','month','Monat','mes');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','textweek','week','Woche','semana');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','textyear','year','Jahr','A�o completo');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('Account','unknownOrdinalisationLanguage','An unknown language was passed to Account::ordinal().','An Account::ordinal wurde eine unbekannte Sprache �bergeben.','Fue pasado un idioma desconocido a Account::ordinal().');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('accountOverviewPlanned','colRepeatText','Repetition','Wiederholung','repetici�n');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('statistics2Graph','only1transaction','Your criteria resulted in only one transaction, of which no line graph can be drawn.','Ihre Kriterien ergaben nur eine Transaktion, woraus kein Liniendiagramm gezeichnet werden kann.','Sus criterios dio lugar a una sola operaci�n, que no puede graficar la linea.');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','stringEqualTo','equals','gleich','igual a');");
	$log .= doQuery("INSERT INTO `i18n` (page_id, id, en, de, es) VALUES ('dataGridFilter','stringNotEqual','not equal','ungleich','no igual');");

	
	
	$log .= "&rarr; Updating database version to 1.0.\n";
	
	$log .= doQuery("REPLACE user_settings SET prop_key = 'badgerDbVersion', prop_value = 's:3:\"1.0\";'");
	


	$log .= "\n&rarr;&rarr; Update to version 1.0 finished. &larr;&larr;\n\n";

	return $log;
}

function doQuery($sql, $acceptableResults = array()) {
	global $badgerDb, $tpl;

	$severeError = getUpdateTranslation('updateUpdate', 'severeError');

	$log = "SQL: $sql\n";

	$result = $badgerDb->query($sql);

	if (PEAR::isError($result)) {
		$log .= 'Query resulted in error. Error code: ' . $result->getCode() . ' Error message: ' . $result->getMessage() . ' Native error message: ' . $result->getUserInfo() . "\n";

		if (array_search($result->getCode(), $acceptableResults) === false) {
			eval('$log .= "' . $tpl->getTemplate('update/severeError') . '";');
		} else {
			$log .= "This error is not severe.\n";
		}
	} else {
		$log .= "Query succeeded. " . $badgerDb->affectedRows() . " rows affected.\n";
	}

	$log .= "\n";

	return $log;
}

function getUpdateTranslation($pageId, $id) {
	global $us;

	static $transTbl = array (
		'updateProcedure' => array (
			'en' => array (
				'pageTitle' => 'Update BADGER finance',
				'legend' => 'Steps to Update',
				'updateInformation' => 'BADGER finance detected an update of its files. This page updates the database. All your data will be preserved.',
				'dbVersionText' => 'Database version:',
				'fileVersionText' => 'File version:',
				'stepDescription' => 'The update consists of two simple steps. First, a backup of the database is saved to your computer. This preserves your data in the rare case anything goes wrong. Second, the database is updated.',
				'step1PreLink' => 'Please click the following link and save the file to your computer.',
				'step1LinkText' => 'Save backup',
				'step1PostLink' => '',
				'step2PreLink' => 'Please click the following link to start the database update.',
				'step2LinkText' => 'Update database',
				'step2PostLink' => ''
			),
			'de' => array (
				'pageTitle' => 'BADGER finance aktualisieren',
				'legend' => 'Schritte zur Aktualisierung',
				'updateInformation' => 'BADGER finance hat eine Aktualisierung seiner Dateien festgestellt. Diese Seite aktualisiert die Datenbank. Ihre Daten bleiben vollst�ndig erhalten.',
				'dbVersionText' => 'Datenbank-Version:',
				'fileVersionText' => 'Datei-Version:',
				'stepDescription' => 'Die Aktualisierung besteht aus zwei einfachen Schritten. Zuerst wird eine Sicherheitskopie der Datenbank auf Ihrem Computer gespeichert. Dadurch bleiben Ihre Daten auch im unwahrscheinlichen Fall eines Fehlschlags erhalten. Anschlie�end wird die Datenbank aktualisiert.',
				'step1PreLink' => 'Bitte klicken Sie auf folgenden Link und speichern Sie die Datei auf Ihrem Computer.',
				'step1LinkText' => 'Sicherungskopie speichern',
				'step1PostLink' => '',
				'step2PreLink' => 'Bitte klicken Sie auf folgenden Link, um die Datenbank-Aktualisierung zu beginnen.',
				'step2LinkText' => 'Datenbank aktualisieren',
				'step2PostLink' => ''
			)
		),
		'updateUpdate' => array (
			'en' => array (
				'pageTitle' => 'Updating BADGER finance',
				'betweenVersionsText' => 'Versions in between:',
				'preCurrentText' => 'Update from',
				'postCurrentText' => 'to',
				'postNextText' => '',
				'logEntryHeader' => 'Information from the update:',
				'updateInformation' => 'BADGER finance is now performing the update. It is performed step-by-step, one step for each version.',
				'errorInformation' => 'Please read the output of the process. If it encounters any severe errors they are written in red. In this case, please send the whole output to the BADGER development team (see help for contact info).',
				'updateFinished' => 'The update has finished.',
				'severeError' => 'The update encountered a severe error. Please send the whole output to the BADGER finance development team.',
				'goToStartPagePreLink' => 'Please ',
				'goToStartPageLinkText' => 'go to start page',
				'goToStartPagePostLink' => ' to continue.'
			),
			'de' => array (
				'pageTitle' => 'BADGER finance wird aktualisiert',
				'betweenVersionsText' => 'Dazwischenliegende Versionen:',
				'preCurrentText' => 'Aktualisierung von',
				'postCurrentText' => 'auf',
				'postNextText' => '',
				'logEntryHeader' => 'Informationen der Aktualisierung:',
				'updateInformation' => 'Die Aktualisierung wird nun durchgef�hrt. Dies findet Schritt f�r Schritt statt, einen Schritt f�r jede Version.',
				'errorInformation' => 'Bitte lesen sie die Ausgabe dieses Prozesses. Die einfachen Informationen sind auf Englisch gehalten. Falls der Prozess irgend welche schweren Fehler meldet, sind diese rot eingef�rbt. Bitte schicken Sie in diesem Fall die gesamte Ausgabe an das BADGER Entwicklungsteam (siehe Hilfe f�r Kontaktinformationen).',
				'updateFinished' => 'Die Aktualisierung ist beendet.',
				'severeError' => 'Die Aktualisierung stie� auf einen schweren Fehler. Bitte schicken Sie die gesamte Ausgabe an das BADGER finance development team.',
				'goToStartPagePreLink' => 'Bitte ',
				'goToStartPageLinkText' => 'zur Startseite gehen',
				'goToStartPagePostLink' => ' um fortzusetzen.'
			)
		)
	);

	$trans = getBadgerTranslation2($pageId, $id);

	if (PEAR::isError($trans) || $trans === '') {
		$trans = $transTbl[$pageId][$us->getProperty('badgerLanguage')][$id];
	}

	return $trans;
}

require_once BADGER_ROOT . '/includes/fileFooter.php';
?>