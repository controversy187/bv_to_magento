<?php
include( 'config.php' );
include( 'custom_functions.php' );

$bvin = $_POST['bvin'];
$mag_id = $_POST['mag_id'];

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

  $select_category = $dbh->prepare( "SELECT * FROM bvc_Category WHERE `bvin` = :bvin_id" );
  $select_category->bindParam(':bvin_id', $bvin);
  $select_category->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_category->fetchObject()){
  //echo "<pre>";var_dump($row);die("</pre>");
  $parent_bvin = $row->ParentID;
  
  if($row->ParentID != "0"){
    //Lookup Parent Magento ID
    try {
      $lookup_parent = $mag_dbh->prepare( "SELECT * FROM bv_x_magento_categories WHERE `bvin` = :bvin_id" );
      $lookup_parent->bindParam(':bvin_id', $parent_bvin);
      $lookup_parent->execute();
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }
    if($response = $lookup_parent->fetchObject()){
      $parent_mag_id = $response->mag_id;
    }

    include( 'api_functions.php' );

    $return = $client->catalogCategoryMove($session, $mag_id, $parent_mag_id);

    if($return) {
      echo "Success - Parent is now $parent_mag_id";
    } else {
      echo "ERROR";
    }
  } else {
    echo "Root category; no move necessary";
  }
  
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>