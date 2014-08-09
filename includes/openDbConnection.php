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
	 * Information required for database connection
	 * 
	 * @var array of strings
	 */
$badgerDbConnectionInfo = array(
	'phptype'=>DB_TYPE,
	'username'=>DB_USERNAME,
	'password'=>DB_PASSWORD,
	'hostspec'=>DB_HOST,
	'database'=>DB_DATABASE_NAME
);

/*
$options = array(
	'portability'=> 'DB_PORTABILITY_ALL'
);
*/

	/**
	 * creation of database object
	 * 
	 * @var object
	 */
$badgerDb =& DB::Connect($badgerDbConnectionInfo, array('debug' => 9999));
if (PEAR::isError($badgerDb)){
	die($badgerDb->getMessage());	
}


?>