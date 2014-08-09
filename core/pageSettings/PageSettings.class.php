<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://www.badger-finance.org 
*
**/

class PageSettings {
	private $pages;
	
	private $badgerDb;
	
	public function PageSettings($badgerDb) {
		$this->badgerDb = $badgerDb;
		$this->pages = array();
	}
	
	public function getSettingNamesList($page) {
		$this->loadPage($page);
		
		if (array_key_exists($page, $this->pages)) {
			return array_keys($this->pages[$page]);
		}		
	}
	
	public function getSettingRaw($page, $settingName) {
		$this->loadPage($page);
		
		return $this->getSetting($page, $settingName);
	}
	
	public function setSettingRaw($page, $settingName, $setting) {
		$this->saveSetting($page, $settingName, $setting);
	}
	
	public function getSettingSer($page, $settingName) {
		$this->loadPage($page);
		
		return unserialize($this->getSetting($page, $settingName));
	}
	
	public function setSettingSer($page, $settingName, $setting) {
		$this->saveSetting($page, $settingName, serialize($setting));
	}
	
	public function deleteSetting($page, $settingName) {
		unset($this->pages[$page][$settingName]);
		
		$sql = "DELETE FROM page_settings
			WHERE page_name = '" . $this->badgerDb->escapeSimple($page)
			. "' AND setting_name = '" . $this->badgerDb->escapeSimple($settingName)
			. "'"
		;

		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('PageSettings', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	private function loadPage($page, $cache = true) {
		if ($cache) {
			if (array_key_exists($page, $this->pages)) {
				return;
			}
		} else {
			unset($this->pages[$page]);
		}
		
		$sql = "SELECT setting_name, setting
			FROM page_settings
			WHERE page_name = '" . $this->badgerDb->escapeSimple($page) . "'"
		;
		
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('PageSettings', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
		
		$row = false;

		while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
			$this->pages[$page][$row['setting_name']] = $row['setting'];
		}
	}
	
	private function getSetting($page, $settingName) {
		if (array_key_exists($page, $this->pages) && array_key_exists($settingName, $this->pages[$page])) {
			return $this->pages[$page][$settingName];
		} else {
			return '';
		}
	}
	
	private function saveSetting($page, $settingName, $setting) {
		$this->pages[$page][$settingName] = $setting;
		
		$sql = "REPLACE page_settings SET page_name = '" . $this->badgerDb->escapeSimple($page)
			. "', setting_name = '" . $this->badgerDb->escapeSimple($settingName)
			. "', setting = '" . $this->badgerDb->escapeSimple($setting)
			. "'"
		;

		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('PageSettings', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
}
?>