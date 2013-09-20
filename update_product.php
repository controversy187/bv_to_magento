<?php
include( 'config.php' );
include( 'custom_functions.php' );
$startTime = time();

$sku = $_POST['sku'];

include( 'api_functions.php' );
    
try{
  $result = $client->catalogProductInfo($session, $sku . ' ', STORE_CODE);
} catch (SoapFault $e) {
  echo "<pre>";var_dump($e);die("</pre>");
}

$old_key 	= $result->url_key;
$old_path = $result->url_path;
$name 		= $result->name;

$dataArray = array(
  'url_key'   => strtolower($sku . ' '),
  'url_path'  => strtolower($sku . '.html')
);

if($old_key == strtolower($sku) && $old_path == strtolower($sku . '.html') ) {
	$timePassed = time() - $startTime;
	echo "Magento Product '" . $sku . "' - " . $name . " - No update required - (" . $timePassed . " seconds total)";
} else {
	//Update the products
	try{
	  $result = $client->catalogProductUpdate($session, $sku . ' ', $dataArray, STORE_CODE);  
	} catch (SoapFault $e){
	  echo "<pre>";var_dump($e);die("</pre>");
	}

	$timePassed = time() - $startTime;
	echo $result . " Magento Product '" . $sku . "' - " . $name . " - URL Key: " . $old_key . " -> " . $dataArray['url_key'] . " - (" . $timePassed . " seconds total)";
}

$mag_dbh = null;
$dbh = null;
?>