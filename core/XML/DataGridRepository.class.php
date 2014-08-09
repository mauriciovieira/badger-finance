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
 * Handle storage of DataGrid handlers.
 * 
 * @author Eni Kao, Paraphil
 * @version $LastChangedRevision: 1218 $ 
 */
class DataGridRepository {
	/**
	 * list of all handlers
	 * 
	 * @var array
	 */
	private $handlers;
	
	/**
	 * database Object
	 * 
	 * @var object
	 */
	private $badgerDb;
	
	/**
	 * reads out all handlers from Database
	 * 
	 * @param object $badgerDb the database object
	 */
    public function DataGridRepository($badgerDb) {
    	$this->badgerDb = $badgerDb;
    	
    	$sql = 'SELECT handler_name, file_path, class_name
			FROM datagrid_handler';
		
		$res =& $badgerDb->query($sql);

		$this->handlers = array();
		
		$row = array();
		
		while ($res->fetchInto($row, DB_FETCHMODE_ASSOC)) {
			$this->handlers[$row['handler_name']] = array (
				'path' => $row['file_path'],
				'class' => $row['class_name']
			);
		}
    }
    
    /**
     * Gets an array with the data of the handler $key
     * 
     * The result has the following form:
     * array (
     *   'path' => '/path/to/the/php/file.class.php',
     *   'class' => 'ClassName'
     * );
     * 
     * @param string $key The name of the requested handler
     * @throws BadgerException if unknown $key is passed
     * @return array the Handler data referenced by $key
     */
    public function getHandler($key) {
    	if (array_key_exists($key, $this->handlers)) {
    		return $this->handlers[$key];
    	} else {
    		throw new BadgerException('DataGridRepository', 'illegalHandlerName', $key);
    	}
    }
    
    /**
     * Sets handler $key
     * 
     * $value has the following form:
     * array (
     *   'path' => '/path/to/the/php/file.class.php',
     *   'class' => 'ClassName'
     * );
     * 
     * @param string $key key of the target handler
     * @param mixed value the value referneced by $key can be every serializable php data
     * @return void
     */
    public function setHandler($key, $value) {
       	if (array_key_exists($key, $this->handlers)) {
    		$sql = 'UPDATE datagrid_handler
				SET file_path = \'' . addslashes($value['path']) . '\', class_name = \'' . addslashes($value['class']) . '\'
				WHERE handler_name = \'' . addslashes($key) . '\'';
    		
    		$this->badgerDb->query($sql);
       	} else {
       		$sql = 'INSERT INTO datagrid_handler (handler_name, file_path, class_name)
				VALUES (\'' . addslashes($key) . '\',
				\'' . addslashes($value['path']) . '\', \'' . addslashes($value['class']) . '\')';
				
			$this->badgerDb->query($sql);	
    		
       	}

       	$this->handlers[$key] = $value;
    }

	/**
	 * Deletes handler $key.
	 * 
	 * @param string $key key of the target handler
	 * @throws BadgerException if unknown $key is passed
	 * @return void 
	 */
 	public function delHandler($key) {
		if (array_key_exists($key, $this->handlers)) {
    		$sql = 'DELETE FROM datagrid_handler
				WHERE handler_name = \'' . addslashes($key) . '\'';
				
    		
    		$this->badgerDb->query($sql);
			  		
    		unset ($this->handlers[$key]);
    	} else {
    		throw new BadgerException('DataGridRepository', 'illegalHandlerName', $key);
    	}
    }
}
?>