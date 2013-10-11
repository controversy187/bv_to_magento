<?php
include( 'config.php' );
include( 'custom_functions.php' );
$startTime = time();

$sku = $_POST['sku'];

include( 'api_functions.php' );

try{
  $attributes->additional_attributes[] = 'isbn_10';
  $attributes->additional_attributes[] = 'upc';
  $attributes->additional_attributes[] = 'trim_size';
  $attributes->additional_attributes[] = 'carton_quantity';
  $result = $client->catalogProductInfo($session, $sku . ' ', null, $attributes);
} catch (SoapFault $e) {
  echo "<pre>";var_dump($e);die("</pre>");
}

$updatedNeeded = false;

if(isset($result->additional_attributes)){
	foreach($result->additional_attributes as $id=>$attr){
		if($attr->value == '1'){
			$attr->value = '';
			$updatedNeeded = true;
		}
		$additional_attributes['single_data'][]  = array('key' => $attr->key, 'value' => $attr->value);
	}
}

if(isset($additional_attributes)){
	$dataArray = array(
		'additional_attributes' => $additional_attributes
	);
}

//Update the products
if($updatedNeeded){
	try{
		$result = $client->catalogProductUpdate($session, $sku . ' ', $dataArray, null);
		$msg = " Magento Product '" . $sku . "' Updated";
	} catch (SoapFault $e){
		echo "<pre>";var_dump($e);die("</pre>");
	}
} else {
		$msg = " Magento Product '" . $sku . "' - No update needed.";
}
$timePassed = time() - $startTime;
echo $msg . " (" . $timePassed . " seconds total)";


$mag_dbh = null;
$dbh = null;
?>