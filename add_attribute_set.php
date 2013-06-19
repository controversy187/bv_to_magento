<?php
include( 'config.php' );
include( 'custom_functions.php' );

$bvin = $_POST['bvin'];

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


// Get BV Data
try {
  # MySQL with PDO_MYSQL  
  $dbh = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=". SRC_DB_NAME, SRC_DB_USER, SRC_DB_PW); 
  $dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  $select_property = $dbh->prepare( "SELECT * FROM bvc_ProductType WHERE `bvin` = :bvin_id" );
  $select_property->bindParam(':bvin_id', $bvin);
  $select_property->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_property->fetchObject()){
  
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_attribute_sets', $mag_dbh)){
    include( 'api_functions.php' );
    if($id = checkAttributeSetExists($row->ProductTypeName, $client, $session)){
      $sql = "INSERT INTO bv_x_magento_attribute_sets (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
      echo "Resuing old id: " . $id . " for set " . $row->ProductTypeName;
      try{
        $mag_dbh->query($sql);
      } catch(PDOException $e) {  
        echo $e->getMessage();
        exit();
      }
    } else {
      $id = $client->catalogProductAttributeSetCreate(
        $session,
        $row->ProductTypeName,
        DEFAULT_ATTRIBUTE_SET_ID // Set this as a child of the default set for products
      );

      $sql = "INSERT INTO bv_x_magento_attribute_sets (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
      try{
        $mag_dbh->query($sql);
      } catch(PDOException $e) {  
        echo $e->getMessage();
        exit();
      }
      echo "Magento Attribute Set ID: " . $id . ", name: " . $row->ProductTypeName ;
    }

  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>