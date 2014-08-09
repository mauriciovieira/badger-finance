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
if (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
} else {
	$mode = 'askData';
}

switch ($mode) {
	case 'compare':
		compare();
		break;
	
	case 'askData':
	default:
		askData();
}

function askData() {
	echo <<<EOT
	<form method="post" action="compareDbContent.php?mode=compare">
		Server: <input name="server" value="localhost" /><br />
		User: <input name="user" value="root" /><br />
		Password: <input name="password" value="" /><br />
		Older database: <input name="oldDb" /><br />
		Newer database: <input name="newDb" /><br />
		<input type="submit" />
	</form>
EOT;
}

function compare() {
	mysql_connect($_POST['server'], $_POST['user'], $_POST['password']) or die (mysql_error());
	
	$oldDb = $_POST['oldDb'];
	$newDb = $_POST['newDb'];
	
	$oldTables = getDbTables($oldDb);
	$newTables = getDbTables($newDb);
	
	echo <<<EOT
<style type="text/css">
	body {
		font-family: Verdana, sans-serif;
	}
	.old {
		background-color: #8888ff;
	}
	.new {
		background-color: #ff8888;
	}
	.even {
		background-color: #eeeeee;
	}
	.odd {
		background-color: #bbbbbb;
	}
	tr:hover {
		background-color: #00dddd;
	}
</style>
EOT;

	echo "Old database: $oldDb<br />\n";
	echo "New database: $newDb<br />\n";
	echo "<br />\n";

	$deletedTables = array_diff($oldTables, $newTables);
	$addedTables = array_diff($newTables, $oldTables);
	$bothTables = array_intersect($newTables, $oldTables);
	
	if (count($deletedTables)) {
		echo "Deleted Tables:<br />\n";
		echo array2Ul($deletedTables);
	}
	if (count($addedTables)) {
		echo "Added Tables:<br />\n";
		echo array2Ul($addedTables);
	}

	foreach ($bothTables as $currentTable) {
		echo "<h2>Table $currentTable</h2>\n";
		
		$oldColumns = getTableColumNames($oldDb, $currentTable);
		$newColumns = getTableColumNames($newDb, $currentTable);
		
		$deletedColumns = array_diff($oldColumns, $newColumns);
		$addedColumns = array_diff($newColumns, $oldColumns);
		$bothColumns = array_intersect($newColumns, $oldColumns);
		
		if (count($deletedColumns)) {
			echo "Deleted Columns:<br />\n";
			echo array2Ul($deletedColumns);
		}
		if (count($addedColumns)) {
			echo "Added Columns:<br />\n";
			echo array2Ul($addedColumns);
		}
		//echo "Both Columns:<br />\n";
		//echo array2Ul($bothColumns);

		$primaryKeys = array();

		foreach($bothColumns as $currentColumn) {
			$oldColumnInfo = getColumInfo($oldDb, $currentTable, $currentColumn);
			$newColumnInfo = getColumInfo($newDb, $currentTable, $currentColumn);
			
			$columnChanges = array();
			foreach($oldColumnInfo as $key => $val) {
				if ($newColumnInfo[$key] !== $val) {
					$columnChanges[$key] = array (
						'old' => $val,
						'new' => $newColumnInfo[$key]
					);
				}
			}
			
			if ($newColumnInfo['Key'] === 'PRI') {
				$primaryKeys[] = $currentColumn;
			}
			
			if (count($columnChanges)) {
				echo "Column $currentColumn changes:<br />\n";
				echo '<table><tr><th>key</th><th>old</th><th>new</th></tr>';
				foreach ($columnChanges as $key => $val) {
					echo "<tr><th>$key</th><td>$val[old]</td><td>$val[new]</td></tr>";
				}
				echo "</table><br />\n";
			}
		}
		
		if (count($primaryKeys) == 0) {
			$primaryKeys = $bothColumns;
		}
		
		$sql = "SELECT o.* FROM `$oldDb`.`$currentTable` o
					LEFT OUTER JOIN `$newDb`.`$currentTable` n ON";
		foreach ($primaryKeys as $val) {
			$sql .= " o.`$val` = n.`$val` AND";
		}
		$sql = substr($sql, 0, strlen($sql) - 3);
		$sql .= " WHERE";
		foreach ($primaryKeys as $val) {
			$sql .= " n.`$val` IS NULL AND";
		}
		$sql = substr($sql, 0, strlen($sql) - 3);
		$result = mysql_query($sql);
//echo "sql: $sql<br />" . mysql_error();
		if (mysql_num_rows($result) != 0) {
			echo "<h3>Deleted rows</h3>\n";
			printHTMLTable($result);
		}
		
		$sql = "SELECT n.* FROM `$newDb`.`$currentTable` n
					LEFT OUTER JOIN `$oldDb`.`$currentTable` o ON";
		foreach ($primaryKeys as $val) {
			$sql .= " n.`$val` = o.`$val` AND";
		}
		$sql = substr($sql, 0, strlen($sql) - 3);
		$sql .= " WHERE";
		foreach ($primaryKeys as $val) {
			$sql .= " o.`$val` IS NULL AND";
		}
		$sql = substr($sql, 0, strlen($sql) - 3);
		$result = mysql_query($sql);
//echo "sql: $sql<br />" . mysql_error();
		if (mysql_num_rows($result) != 0) {
			echo "<h3>Added rows</h3>\n";
			printHTMLTable($result);
		}
				
		$otherColumns = array_diff($bothColumns, $primaryKeys);
		if (count($otherColumns) != 0) {
			$sql = "SELECT o.*, n.* FROM `$newDb`.`$currentTable` n
						INNER JOIN `$oldDb`.`$currentTable` o ON";
			foreach ($primaryKeys as $val) {
				$sql .= " n.`$val` = o.`$val` AND";
			}
			$sql = substr($sql, 0, strlen($sql) - 3);
			$sql .= " WHERE";
			foreach ($otherColumns as $val) {
				$sql .= " NOT o.`$val` <=> n.`$val` OR"; 
			}
			$sql = substr($sql, 0, strlen($sql) - 2);
			$result = mysql_query($sql);
//echo "sql: $sql<br />" . mysql_error();
			if (mysql_num_rows($result) != 0) {
				echo "<h3>Changed rows</h3>\n";
				$keys = array();
				for ($i = 0; $i < mysql_num_fields($result); $i++) {
					$keys[] = mysql_field_name($result, $i);
				}
				echo "<table>\n";
				echo '<tr>';
				for ($i = 0; $i < count($keys) / 2; $i++) {
					echo "<th>$keys[$i]</th>";
				}
				echo "</tr>";
				$data = array();
				$currData = 0;
				while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
					echo '<tr class="old">';
					for ($i = 0; $i < count($keys) / 2; $i++) {
						echo '<td>' . htmlentities($row[$i]) . '</td>';
					}
					echo "</tr>\n";
					echo '<tr class="new">';
					for ($i = count($keys) / 2; $i < count($keys); $i++) {
						echo '<td>' . htmlentities($row[$i]) . '</td>';
						$data[$currData][] = $row[$i];
					}
					echo "</tr>\n";
					
					$currData++;
				}
				echo "</table>\n";
				
				echo "<pre>\n";
				foreach ($data as $row) {
					echo "xxSTARTxx SET";
					$firstRow = true;
					foreach ($row as $key => $val) {
						if (!$firstRow) {
							echo ",";
						} else {
							$firstRow = false;
						}
						echo " $keys[$key] = '" . mysql_escape_string($val) . "'";
					}
					echo "\n";
				}
				echo "</pre>\n";
			} // if changed rows
		} // if otherColumns
		else {
			echo "Could not compare changed rows due to the lack of primary keys.<br />\n";
		}
	} // foreach table
} // function compare

function getDbTables($dbName) {
	$sql = "SHOW TABLES FROM `$dbName`";
	$result = mysql_query($sql);
	$tables = array();
	while ($row = mysql_fetch_array($result)) {
		$tables[] = $row[0];
	}
	asort($tables);
	
	return $tables;
}

function getTableColumNames($dbName, $tableName) {
	$sql = "DESCRIBE `$dbName`.`$tableName`";
	$result = mysql_query($sql);
	$columns = array();
	while ($row = mysql_fetch_array($result)) {
		$columns[] = $row[0];
	}
	asort($columns);
	
	return $columns;
}

function getColumInfo($dbName, $tableName, $columnName) {
	$sql = "DESCRIBE `$dbName`.`$tableName` `$columnName`";
	$result = mysql_query($sql);

	return mysql_fetch_array($result, MYSQL_ASSOC);
}

function array2Ul($array) {
	$result = '<ul>';
	foreach ($array as $val) {
		$result .= "<li>$val</li>";
	}
	$result .= "</ul>\n";
	
	return $result;
}

function printHTMLTable($result) {
	echo "<table>\n";
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	echo '<tr>';
	foreach ($row as $key => $val) {
		echo "<th>$key</th>";
	}
	echo "</tr>\n";
	$odd = true;
	$data = array();
	do {
		echo '<tr class="' . ($odd ? 'odd' : 'even') . '">';
		foreach ($row as $val) {
			echo '<td>' . htmlentities($val) . '</td>';
		}
		$data[] = $row;
		echo "</tr>\n";
		$odd = !$odd;
	} while ($row = mysql_fetch_array($result, MYSQL_ASSOC));
	echo "</table>\n";
	
	echo "<pre>\n";
	foreach ($data as $row) {
		echo "xxSTARTxx SET";
		$firstRow = true;
		foreach ($row as $key => $val) {
			if (!$firstRow) {
				echo ",";
			} else {
				$firstRow = false;
			}
			echo " $key = '" . mysql_escape_string($val) . "'";
		}
		echo "\n";
	}
	echo "</pre>";
}
?>