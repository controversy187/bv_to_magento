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

  $select_property = $dbh->prepare( "SELECT * FROM bvc_ProductProperty WHERE `bvin` = :bvin_id" );
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
    $PropertyName = $row->PropertyName;
    if($row->PropertyName == ""){
      $PropertyName = $row->bvin;
    }
    $PropertyName = str_replace(' ', '_', $PropertyName);
    $PropertyName = str_replace('(', '_', $PropertyName);
    $PropertyName = str_replace(')', '_', $PropertyName);
    $PropertyName = str_replace(':', '_', $PropertyName);
    $PropertyName = str_replace('-', '_', $PropertyName);
    if (!preg_match('/^[a-z]/i', $PropertyName)) { // Make sure it starts with a letter.
      $PropertyName = 'a' . $PropertyName;
    }
    $PropertyName = strtolower($PropertyName);
    $PropertyName = substr ( $PropertyName, 0, 29 ); // Make sure it is under 30 characters in length

    if($id = array_search($PropertyName, $attributes)){
      $sql = "INSERT INTO bv_x_magento_attributes (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
      echo "Resuing old id: " . $id . " for attribute " . $PropertyName;
      try{
        $mag_dbh->query($sql);
      } catch(PDOException $e) {  
        echo $e->getMessage();
        exit();
      }
    } else {

      // Convert BV Type Codes to Magento Frontend Input Types.
      switch ($row->TypeCode) {
        case '1':
          $frontend_input = 'text';
          break;
        case '2':
          $frontend_input = 'select';
          break;
        case '3':
          $frontend_input = 'price';
          break;
        case '4':
          $frontend_input = 'date';
          break;
        default:
          $frontend_input = 'text';
          break;
      }      
     
      $data = array(
        "attribute_code" => $PropertyName,
        "frontend_input" => $frontend_input,
        "scope" => "global",
        "default_value" => "1",
        "is_unique" => 0,
        "is_required" => 0,
        "apply_to" => array(),
        "is_configurable" => 0,
        "is_searchable" => 1,
        "is_visible_in_advanced_search" => 1,
        "is_comparable" => 1,
        "is_used_for_promo_rules" => 1,
        "is_visible_on_front" => 1,
        "used_in_product_listing" => 1,
        "additional_fields" => array(),
        "frontend_label" => array(array("store_id" => "0", "label" => $row->DisplayName))
      );

      include( 'api_functions.php' );

      try{
        $id = $client->catalogProductAttributeCreate($session, $data);  
      } catch (SoapFault $e) { 
        if($e->faultcode == "105"){  // Attribute already exists, but isn't in a set -> We didn't catch it earlier
          $attribute_info = $client->catalogProductAttributeInfo($session, $PropertyName);
          $id = $attribute_info->attribute_id;
          echo "Resuing old id: $id for attribute $PropertyName";
        }
      } 
      

      $sql = "INSERT INTO bv_x_magento_attributes (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
      try{
        $mag_dbh->query($sql);
      } catch(PDOException $e) {  
        echo $e->getMessage();
        exit();
      }

      echo " Magento Attribute ID: " . $id;
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