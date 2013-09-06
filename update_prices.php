<?php
include( 'config.php' );
include( 'custom_functions.php' );
$startTime = time();

$sku 		= $_POST['sku'];
$price 	= $_POST['price'];

include( 'api_functions.php' );
    
try{
  $result = $client->catalogProductInfo($session, $sku . ' ', STORE_CODE);
} catch (SoapFault $e) {
 	if($e->faultstring == "Product not exists.") {
    $timePassed = time() - $startTime;
		die("ERROR: Couldn't locate SKU " . $sku . " - (" . $timePassed . " seconds total)");
	} else {
  	echo "<pre>";var_dump($e);die("</pre>");
  }
}

$name = $result->name;

$additional_attributes['single_data'][]  = array('key' => 'msrp', 'value' => $price);

$dataArray = array(
  'price'   							=> $price,
  'additional_attributes' => $additional_attributes
);

//Update the products
try{
  $result = $client->catalogProductUpdate($session, $sku . ' ', $dataArray, STORE_CODE); 
  } catch (SoapFault $e){
	if($e->faultstring == "Product not exists.") {
		die("ERROR: Couldn't locate SKU " . $sku . " - (" . $timePassed . " seconds total)");
	} else {
  	echo "<pre>";var_dump($e, $client);die("</pre>");
  }
}

$timePassed = time() - $startTime;
echo $result . "   Magento Product '" . $sku . "' -> $" . $price . " - (" . $timePassed . " seconds total)";


$mag_dbh = null;
$dbh = null;
?>