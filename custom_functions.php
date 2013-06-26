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

/**
 * Returns a string formatted for the Magento Attribute Code parameter
 * @param  String $PropertyName An unformatted string
 * @return String               A string formatted for Magento Attribute Code
 */
function formatPropertyName($PropertyName){
	$PropertyName = str_replace(' ', '_', $PropertyName);
  $PropertyName = str_replace('(', '_', $PropertyName);
  $PropertyName = str_replace(')', '_', $PropertyName);
  $PropertyName = str_replace(':', '_', $PropertyName);
  $PropertyName = str_replace('-', '_', $PropertyName);
  if (!preg_match('/^[a-z]/i', $PropertyName)) { // Make sure it starts with a letter.
    $PropertyName = 'a' . $PropertyName;
  }
  $PropertyName = strtolower($PropertyName);
  $PropertyName = substr ( $PropertyName, 0, 29 ); // Make sure it is under 30 characters in length

  return $PropertyName;
}


/**
 * Gets the additional attributes from the BV data and returns an array formated for Magento's SOAP API v2
 * @param  String $bvin The product Bvin
 * @param  PDO $dbh  The PDO database connection to the BV data
 * @return Array       An array for the additional_attributes parameter.
 */
function getAdditionalAttributes($bvin, $dbh, $mag_dbh = null){
	$codesOfValues = array('isbn', 'pages', 'crp');
	try {
	  # MySQL with PDO_MYSQL  
	  $result = $dbh->prepare( "SELECT * FROM `bvc_ProductPropertyValue` WHERE `ProductBvin` = :bvin_id" );
	  $result->bindParam(':bvin_id', $bvin);
	  $result->execute();
	} catch(PDOException $e) {   
	  echo $e->getMessage();
	  exit();
	}
	
	$additional_attributes = array();
	$startTimeNow = time();
	while ($row = $result->fetchObject()) {
		$propertyValue = getPropertyValue($row->PropertyValue, $dbh);
		$attributeCode = getMagAttributeCodeFromBvin($row->PropertyBvin, $dbh);

		//echo "<pre>";var_dump($propertyValue, $attributeCode);echo("</pre>");

		if(in_array($attributeCode, $codesOfValues)){
			$attributeValue = $propertyValue;
		} else {
			$attributeValue = getAttributeValueFromPropertyCode($propertyValue, $attributeCode, $mag_dbh);
		}

		$additional_attributes[] = array(
			'key' 	=> $attributeCode,
			'value' => iconv ( "windows-1252" , "UTF-8" , $attributeValue )
		);
	}
	
	return $additional_attributes;
}

/**
 * This takes a label of an attibute option and looks up the value.
 * If it cannot find a match, it returns the original label input as it is assumed to be
 * a non-option attribute (text field, etc) and therefore not lookupable.
 * It caches the results in the local DB to speed up future duplicate requests
 * @param  String $propertyValue The label of the option
 * @param  String $attributeCode The code of the attribute
 * @return String                The value of the id of the option or the input label
 */
function getAttributeValueFromPropertyCode($propertyValue, $attributeCode, $dbh = null){
	// First, we try to look up the result from our own DB, as that is significantly faster
	try {
	  # MySQL with PDO_MYSQL  
	  $result = $dbh->prepare( "SELECT value FROM `bv_x_attribute_value_codes` WHERE `label` = :label AND `code` = :code" );
	  $result->bindParam(':label', $propertyValue);
	  $result->bindParam(':code', $attributeCode);
	  $result->execute();
	} catch(PDOException $e) {   
	  echo $e->getMessage();
	  //exit();
	}

	if ($row = $result->fetchObject()) {
		return $row->value;
	}

	// If we didn't find a local result, we start the process for looking up the result via the API
	$value = $propertyValue;

	include( 'api_functions.php' );
	
	$result = $client->catalogProductAttributeOptions($session, $attributeCode, STORE_CODE);
	foreach($result as $option){
		if($propertyValue == $option->label){
			$value = $option->value;
		}

		// Store all possible results in the local DB for caching later
		$sql = "INSERT INTO bv_x_attribute_value_codes (`label`, `code`, `value`) VALUES ( '" . $option->label . "', '" . $attributeCode . "', '" . $option->value . "'  );";
		try{
	    $dbh->query($sql);
	  } catch(PDOException $e) {  
	    echo $e->getMessage();
	    exit();
	  }
	}

	return $value;
	
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

	$catIDs = array();
	//TODO: This needs to iterate, not return
	while($response = $bv_cat->fetchObject()){
		$catIDs[] = $response->CategoryId;
  } 
  return $catIDs;
}
	
/**
 * Returns the Magento Code of an attribute property based on its Property Bvin
 * @param  String $bvin The property Bvin
 * @param  PDO $dbh  The PDO database connection to the BV data
 * @return String                    The Magento attribute name
 */
function getMagAttributeCodeFromBvin($bvin, $dbh){
	try {
	  # MySQL with PDO_MYSQL  
	  $bv_prop = $dbh->prepare( "SELECT bvin, PropertyName FROM `bvc_ProductProperty` WHERE `bvin` = :bvin_id" );
	  $bv_prop->bindParam(':bvin_id', $bvin);
	  $bv_prop->execute();
	} catch(PDOException $e) {  
	  echo $e->getMessage();
	  exit();
	}

	if($row = $bv_prop->fetchObject()){
    if($row->PropertyName == ""){
      return formatPropertyName($row->bvin);
    } else {
    	return formatPropertyName($row->PropertyName);
    }
  } else {
  	return false;
  }	
}

function getPropertyValue($propertyValue, $dbh){
	if(preg_match("/^[a-f0-9]{8}(-[a-f0-9]{4}){3}-[a-f0-9]{12}$/i", $propertyValue)){ // If we have a bvin, not a real value...
		try {
		  # MySQL with PDO_MYSQL  
		  $bv_choice = $dbh->prepare( "SELECT ChoiceName FROM `bvc_ProductPropertyChoice` WHERE `bvin` = :bvin_id" );
		  $bv_choice->bindParam(':bvin_id', $propertyValue);
		  $bv_choice->execute();
		} catch(PDOException $e) {  
		  echo $e->getMessage();
		  exit();
		}

		if($row = $bv_choice->fetchObject()){
	    return $row->ChoiceName;
	  } else {
	  	return false;
	  }	
		
	} else {
		return $propertyValue;
	}
	
}
 
function bvCategoriesToMagentoCategoryIds($category_bvins, $mag_dbh){
	$cat_ids = array();
	foreach($category_bvins as $cat_bvin){
		$cat_ids[] = bvinToMag('bv_x_magento_categories', $cat_bvin, $mag_dbh);
	}
	return $cat_ids;
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