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

$name = $result->name;

$dataArray = array(
  'url_key'   => strtolower($sku . ' '),
  'url_path'  => strtolower($sku . '.html')
);

//Update the products
try{
  $result = $client->catalogProductUpdate($session, $sku . ' ', $dataArray, STORE_CODE);  
} catch (SoapFault $e){
  echo "<pre>";var_dump($e);die("</pre>");
}

$timePassed = time() - $startTime;
echo $result . "   Magento Product '" . $sku . "' - " . $name . " - (" . $timePassed . " seconds total)";


$mag_dbh = null;
$dbh = null;
?>