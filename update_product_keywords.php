<?php
include( 'config.php' );
include( 'custom_functions.php' );
$startTime = time();

// Get BV Data
try {
  # MySQL with PDO_MYSQL  
  $dbh = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=". SRC_DB_NAME, SRC_DB_USER, SRC_DB_PW); 
  $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  $bv_request = $dbh->prepare( "SELECT * FROM `bvc_Product` WHERE `bvin` = :bvin_id" );
  $bv_request->bindParam(':bvin_id', $_POST['bvin']);
  $bv_request->execute();

  $bv_data = $bv_request->fetchObject();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

//echo "<pre>";var_dump($bv_data);die("</pre>");
$sku = $bv_data->SKU;

include( 'api_functions.php' );
    
try{
  $result = $client->catalogProductInfo($session, $sku . ' ', STORE_CODE);
} catch (SoapFault $e) {
	if($e->faultstring == 'Product not exists.'){
		die("SKU " . $sku . " not found.");
	}
  echo "<pre>";var_dump($e);die("</pre>");
}

if( isset($result->meta_keyword)){
	if(false === strpos($result->meta_keyword, iconv ( "windows-1252" , "UTF-8" , $bv_data->Keywords ))){
		//This data needs to be saved; there is new content
		$duplicate = false;
		$keywords = $result->meta_keyword . ', ' . iconv ( "windows-1252" , "UTF-8" , $bv_data->Keywords );
	} else {
		//This data has already been incorporated
		$duplicate = true;
		$keywords = $result->meta_keyword;
	}
} else {
	$keywords = iconv ( "windows-1252" , "UTF-8" , $bv_data->Keywords );
}
$name 		= $result->name;

$dataArray = array(
  'meta_keyword'   => $keywords
);

//echo "<pre>";var_dump($dataArray);die("</pre>");

//Update the products
if($duplicate){
	$timePassed = time() - $startTime;
	echo "No Data Update needed for SKU '" . $sku . "' - Magento Keywords: " . $keywords , " - BV: " . $result->meta_keyword, iconv ( "windows-1252" , "UTF-8" , $bv_data->Keywords ) . " - (" . $timePassed . " seconds total)";
} else {
	try{
		$result = $client->catalogProductUpdate($session, $sku . ' ', $dataArray, STORE_CODE);  
	} catch (SoapFault $e){
		echo "<pre>";var_dump($e);die("</pre>");
	}
	
	$timePassed = time() - $startTime;
	echo "Magento Product '" . $sku . "' - New Keywords: " . $keywords , " - (" . $timePassed . " seconds total)";
}



$mag_dbh = null;
$dbh = null;
?>