<?php

function checkTable($table, $dbh){
	return $dbh->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
}

function checkBvinExists($bvin, $table, $dbh){
	return $dbh->query("SELECT count(*) FROM $table WHERE `bvin` = '$bvin'")->fetchColumn() > 0;
}

/**
 * Looks up a Magento id from a table that maps Bvins to Magento IDs
 * @param  String $table Name of the table that contains the mappings
 * @param  String $bvin  The Bvin
 * @param  PDO $dbh   The PDO Database connection
 * @return Int        The Magento ID
 */
function bvin_to_mag($table, $bvin, $dbh){
	try {
	  # MySQL with PDO_MYSQL  
	  $magento_id = $dbh->prepare( "SELECT * FROM `" . mysql_real_escape_string($table) . "` WHERE `bvin` = :bvin_id" );
	  $magento_id->bindParam(':bvin_id', $bvin);
	  $magento_id->execute();
	} catch(PDOException $e) {  
	  echo $e->getMessage();
	  exit();
	}

	if($response = $magento_id->fetchObject()){
    return $response->mag_id;
  } else {
  	return false;
  }
	
}
?>