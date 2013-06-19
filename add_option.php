<?php
include( 'config.php' );
include( 'custom_functions.php' );

$product_bvin = $_POST['product_bvin_id'];
$choice_bvin  = $_POST['choice_bvin_id'];

// Establish connection to Magento DB
// Get Magento ID for the BV Product
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
  $mag_product_id = bvinToMag('bv_x_magento_products', $product_bvin, $mag_dbh);
}  
catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

/*
try {
  # MySQL with PDO_MYSQL  
  $dbh = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=". SRC_DB_NAME, SRC_DB_USER, SRC_DB_PW); 
  $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  $select_property = $dbh->prepare( "SELECT * FROM bvc_ProductXChoice WHERE `bvin` = :bvin_id" );
  $select_property->bindParam(':bvin_id', $bvin);
  $select_property->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}
*/

echo "<pre>";var_dump($mag_product_id);die("</pre>");

//Get the BV Product Choice Information


if($row = $select_property->fetchObject()){
  
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_attributes', $mag_dbh)){
    
    $data = array(
      
    );
    include( 'api_functions.php' );
    $id = $client->catalogProductCustomOptionAdd($session, $data);

    $sql = "INSERT INTO bv_x_magento_options (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
    try{
      $mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }

    echo "Magento Option ID: " . $id;

  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>