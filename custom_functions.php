<?php

function checkTable($table, $dbh){
	return $dbh->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
}

function checkBvinExists($bvin, $table, $dbh){
	return $dbh->query("SELECT count(*) FROM $table WHERE `bvin` = '$bvin'")->fetchColumn() > 0;
}

?>