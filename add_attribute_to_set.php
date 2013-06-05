<?php
include( 'config.php' );
include( 'api_functions.php' );
include( 'custom_functions.php' );

$bvin = $_POST['bvin'];
$set_bvin = $_POST['set_bvin'];

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
}  
catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}


$attribute_id = bvinToMag('bv_x_magento_attributes', $bvin, $mag_dbh);
$set_id = bvinToMag('bv_x_magento_attribute_sets', $set_bvin, $mag_dbh);

$return = $client->catalogProductAttributeSetAttributeAdd(
    $session,
    $attribute_id,
    $set_id
);

if($return) {
  echo "Success - Attribute $attribute_id is a part of set $set_id";
} else {
  echo "ERROR: Attribute $bvin -> $attribute_id, Set $set_bvin -> $set_id";
}

$mag_dbh = null;
?>