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
 * Supplies the data to a DataGrid in XML.
 * 
 * @author Eni Kao, Para Phil 
 * @version $LastChangedRevision: 1004 $
 */
class DataGridXML {

	/**
	 * All column heads in the table.
	 * 
	 * @var array Simple string array of column names
	 */
	private $columns;
	
	/**
	 * The single rows in the table.
	 * 
	 * @var array Array of array of cell data
	 */
	private $rows;

	/**
	 * Initializes serializer and sets inital column and row data if given.
	 * 
	 * @param array $columns The columns to initialize the Object with
	 * @param array $rows The rows of the Object
	 */
	function __construct() {
		$numArgs = func_num_args();
		
		// The constructor can't be overloaded the usual way so we have to use this workaround.		
		if ($numArgs == 2) {
			
			// func_get_arg() can't be used as a function parameter.
			$columns = func_get_arg(0);
			$rows = func_get_arg(1);
			$this->setData($columns, $rows);
		}
	}
	
	/**
	 * Sets the column head and row data.
	 * 
	 * @param array $columns Array of strings of cloumn names
	 * @param array $rows Array of array of cell data
	 * @return void 
	 */
	public function setData($columns, $rows) {
		$this->setColumns($columns);
		$this->setRows($rows);
	}

	/**
	 * Sets the column head.
	 * 
	 * @param array $columns Array of strings of cloumn names
	 * @return void
	 */
	public function setColumns($columns) {
		if (is_array($columns)) {
			$this->columns = $columns;
		}
	}

	/**
	 * Sets the rows.
	 *
	 * @param array $rows Array of array of cell data
	 * @return void 
	 */
	public function setRows($rows) {
		if (is_array($rows)) {
			$this->rows = $rows;
		}
	}

	/**
	 * Adds several rows.
	 * 
	 * Although works if no data has been given yet.
	 * 
	 * @param array $rows Array of array of cell data
	 * @return void 
	 */
	public function addRows($rows) {
		
		// checks if we have already data 
		if (is_array($this->rows)) {
			if (is_array($rows)) {
				$this->rows = array_merge($this->rows, $rows);
			}
		} else {
			$this->setRows($rows);
		}
	}
	
	/**
	 * Adds a single row.
	 * 
	 * @param array $row Array of cell data
	 * @return void
	 */
	public function addRow($row) {
		if (is_array($row)) {
			$this->rows[] = $row;
		}
	} 
	
	/**
	 * All rows will be erased.
	 * 
	 * @return void 
	 */
	public function emptyRows() {
		$this->rows = null;
	}		
	
	/**
	 * Returns the XML structure.
	 * 
	 * Structure:
	 * <datatable>
	 *   <columns>
	 *     <column>column_name_0</column>
	 *     <column>column_name_1</column>
	 *     <column>column_name_2</column>
	 *   </columns>
	 *   <rows>
	 *     <row>
	 *       <cell>Value of row 0, column 0</cell>
	 *       <cell>Value of row 0, column 1</cell>
	 *       <cell>Value of row 0, column 2</cell>
	 *     </row> 
	 *     <row>
	 *       <cell>Value of row 1, column 0</cell>
	 *       <cell>Value of row 1, column 1</cell>
	 *       <cell>Value of row 1, column 2</cell>
	 *     </row> 
	 *     <row>
	 *       <cell>Value of row 2, column 0</cell>
	 *       <cell>Value of row 2, column 1</cell>
	 *       <cell>Value of row 2, column 2</cell>
	 *     </row>
	 *   </rows>
	 * </datatable> 
	 * 
	 * @return string XML Structure
	 */
	public function getXML() {
		if (!is_array($this->columns)) {
			throw new BadgerException('DataGridXML', 'undefinedColumns');
		}
		
		$result = "<datatable>";

		$result .= "<columns>";
		foreach ($this->columns as $col) {
			$result .= "<column>$col</column>";
		}
		$result .= "</columns>";
		
		$result .= "<rows>";
		foreach ($this->rows as $row) {
			$result .= "<row>";
			foreach ($row as $field) {
				if (!is_array($field)) {
					$field = urlencode($field);
					$result .= "<cell>$field</cell>";
				} else {
					$result .= '<cell ';
					foreach ($field as $attrName => $attrVal) {
						if ($attrName != 'content') {
							$attrVal = urlencode($attrVal);
							$result .= "$attrName=\"$attrVal\" ";
						}
					}
					if (isset($field['content'])) {
						$content = urlencode($field['content']);
					} else {
						$content = '';
					}
					$result .= ">$content</cell>";
				}
			}
			$result .= "</row>";
		}
		$result .= "</rows>";
		
		$result .= "</datatable>";
		
		return $result;
	}
}
?>