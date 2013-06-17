<?php

function checkTable($table, $dbh){
	return $dbh->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
}

function checkBvinExists($bvin, $table, $dbh){
	return $dbh->query("SELECT count(*) FROM $table WHERE `bvin` = '$bvin'")->fetchColumn() > 0;
}

function checkAttributeSetExists($productTypeName, $client, $session){
	$result = $client->catalogProductAttributeSetList($session);
	
	foreach ($result as $set) {
		if(isset($set->name) && $set->name == $productTypeName) return $set->set_id;
	}

	return false;
}

function getAttributeArray($client, $session){
	$attributeArray = array();

	$result = $client->catalogProductAttributeSetList($session);
	
	foreach ($result as $set) {
		$attributes = $client->catalogProductAttributeList($session, $set->set_id);
		foreach ($attributes as $attribute) {
			$attributeArray[$attribute->attribute_id] = $attribute->code;
		}
	}
	return $attributeArray;
}

/**
 * Looks up a Magento id from a table that maps Bvins to Magento IDs
 * @param  String $table Name of the table that contains the mappings
 * @param  String $bvin  The Bvin
 * @param  PDO $dbh   The PDO Database connection
 * @return Int        The Magento ID
 */
function bvinToMag($table, $bvin, $dbh){
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

function getBVCategoryFromProductBvin($product_bvin, $dbh){
	try {
	  # MySQL with PDO_MYSQL  
	  $bv_cat = $dbh->prepare( "SELECT CategoryId FROM `bvc_ProductXCategory` WHERE `ProductId` = :bvin_id" );
	  $bv_cat->bindParam(':bvin_id', $product_bvin);
	  $bv_cat->execute();
	} catch(PDOException $e) {  
	  echo $e->getMessage();
	  exit();
	}

	if($response = $bv_cat->fetchObject()){
    return $response->CategoryId;
  } else {
  	return false;
  }
}

function generateRandomString($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $randomString;
}
?>