<?php

function checkTable($table, $db_handle){
	$tableExists = $db_handle->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;

	return $tableExists;
}

?>