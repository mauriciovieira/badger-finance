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

/**
 * Handle storage of user settings.
 *
 * @author Eni Kao, Paraphil
 * @version $LastChangedRevision: 1218 $
 */
class UserSettings {
	/**
	 * list of all properties
	 *
	 * @var array
	 */
	private $properties;

	/**
	 * database Object
	 *
	 * @var object
	 */
	private $badgerDb;

	/**
	 * reads out all properties from Database
	 *
	 * @param object $badgerDb the database object
	 */
	public function UserSettings($badgerDb) {
		$this->badgerDb = $badgerDb;
		 
		$sql = 'SELECT prop_key, prop_value
			FROM user_settings';

		$res =& $badgerDb->query($sql);

		if (PEAR::isError($res)) {
			echo "SQL ERROR!:<br />SQL: $sql<br />ErrorMessage: " . $res->getMessage() . '<br />Details: ' . $res->getUserInfo() . "<br />\n";
		}
		$this->properties = array();

		$row = array();

		while ($res->fetchInto($row, DB_FETCHMODE_ASSOC)) {
			//echo $row['prop_value'] . ' : ' . unserialize($row['prop_value']) . "<br>";
			$this->properties[$row['prop_key']] = unserialize($row['prop_value']);
		}
	}

	/**
	 * reads out the property defined by $key
	 *
	 * @param string $key key of the requested value
	 * @throws BadgerException if unknown key is passed
	 * @return mixed the value referenced by $key
	 */
	public function getProperty($key) {
		if (array_key_exists($key, $this->properties)) {
			return $this->properties[$key];
		} else {
			throw new BadgerException('UserSettings', 'illegalKey', $key);
		}
	}

	/**
	 * sets property $key to $value
	 *
	 * @param string $key key of the target value
	 * @param mixed value the value referneced by $key can be every serializable php data
	 * @return void
	 */
	public function setProperty($key, $value) {
		if (array_key_exists($key, $this->properties)) {
			$sql = 'UPDATE user_settings
				SET prop_value = \'' . $this->badgerDb->escapeSimple(serialize($value)) . '\'
				WHERE prop_key = \'' . $this->badgerDb->escapeSimple($key) . '\'';

			$this->badgerDb->query($sql);
		} else {
			$sql = 'INSERT INTO user_settings (prop_key, prop_value)
				VALUES (\'' . $this->badgerDb->escapeSimple($key) . '\',
				\'' . $this->badgerDb->escapeSimple(serialize($value)) . '\')';

			$this->badgerDb->query($sql);

		}

		//echo "<pre>$sql</pre>";
		//echo $this->badgerDb->getMessage();
		$this->properties[$key] = $value;
	}

	/**
	 * deletes property $key
	 *
	 * @param string $key key of the target value
	 * @throws BadgerException if unknown key is passed
	 * @return void
	 */
	public function delProperty($key) {
		if (array_key_exists($key, $this->properties)) {
			$sql = 'DELETE FROM user_settings
				WHERE prop_key = \'' . $this->badgerDb->escapeSimple($key) . '\'';


			$this->badgerDb->query($sql);
			 
			unset ($this->properties[$key]);
		} else {
			throw new BadgerException('UserSettings', 'illegalKey', $key);
		}
	}
}
?>