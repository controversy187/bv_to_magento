<?php
include( 'config.php' );
include( 'custom_functions.php' );
$startTime = time();


$sku = $_POST['sku'];
include( 'api_functions.php' );
    
try{
  $result = $client->catalogProductInfo($session, $sku . ' ', STORE_CODE);
} catch (SoapFault $e) {
	if($e->faultstring == 'Product not exists.'){
		die("SKU " . $sku . " not found.");
	}
  echo "<pre>";var_dump($e);die("</pre>");
}

echo "<pre>";var_dump($result->websites);die("</pre>");

//echo "<pre>";var_dump($dataArray);die("</pre>");

//Update the products
if(isset($duplicate) && $duplicate == true){
	$timePassed = time() - $startTime;
	echo "No Data Update needed for SKU '" . $sku . "' - Magento Keywords: " . $keywords , " - BV: " . $result->meta_keyword, iconv ( "windows-1252" , "UTF-8" , $bv_data->Keywords ) . " - (" . $timePassed . " seconds total)";
} else {
	try{
		//$result = $client->catalogProductUpdate($session, $sku . ' ', $dataArray, STORE_CODE);  
	} catch (SoapFault $e){
		echo "<pre>";var_dump($e);die("</pre>");
	}
	
	$timePassed = time() - $startTime;
	echo "Magento Product '" . $sku . "' - New Keywords: " . $keywords , " - (" . $timePassed . " seconds total)";
}



$mag_dbh = null;
$dbh = null;
?>