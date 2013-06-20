<?php
include( 'config.php' );
include( 'custom_functions.php' );

$bvin       = $_POST['bvin'];
$attributes = array_filter(json_decode(stripslashes($_POST['attributes'])));

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

  $select_property = $dbh->prepare( "SELECT * FROM bvc_ProductPropertyChoice WHERE `bvin` = :bvin_id" );
  $select_property->bindParam(':bvin_id', $bvin);
  $select_property->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_property->fetchObject()){
  
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_attributes', $mag_dbh)){

    // Catch (some) illegal characters for the Property Name
    $OptionName = $row->ChoiceName;
   
    /*
    if($row->OptionName == ""){
      $OptionName = $row->bvin;
    }
    $OptionName = str_replace(' ', '_', $OptionName);
    $OptionName = str_replace('(', '_', $OptionName);
    $OptionName = str_replace(')', '_', $OptionName);
    $OptionName = str_replace(':', '_', $OptionName);
    $OptionName = str_replace('-', '_', $OptionName);
    if (!preg_match('/^[a-z]/i', $OptionName)) { // Make sure it starts with a letter.
      $OptionName = 'a' . $OptionName;
    }
    $OptionName = strtolower($OptionName);
    $OptionName = substr ( $OptionName, 0, 29 ); // Make sure it is under 30 characters in length
    */
   
   $attributeID = bvinToMag('bv_x_magento_attributes', $row->bvin, $mag_dbh);
   
    $label = array(array(
      'store_id'  => array(STORE_ID),
      'value'     => $OptionName
    ));

    $data = array(
      'label'       => $label,
      'order'       => $row->SortOrder,
      'is_default'  => 0
    );

    include( 'api_functions.php' );

    try{
      $id = $client->catalogProductAttributeAddOption($session, $attributeID, $data);  
    } catch (SoapFault $e) { 
      echo "<pre>";var_dump($e->faultstring);die("</pre>");
    } 
    

    $sql = "INSERT INTO bv_x_magento_attributes_options (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
    try{
      $mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }

    echo " Magento Attribute ID: " . $id;

  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>