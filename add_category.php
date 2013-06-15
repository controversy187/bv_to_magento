<?php
include( 'config.php' );
include( 'api_functions.php' );
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

  $select_category = $dbh->prepare( "SELECT * FROM bvc_Category WHERE `bvin` = :bvin_id" );
  $select_category->bindParam(':bvin_id', $bvin);
  $select_category->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_category->fetchObject()){
  $bv_category[$row->bvin] = $row;
  
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_categories', $mag_dbh)){
    
    // Create the Category
    $id = $client->catalogCategoryCreate($session, ROOT_CATEGORY_ID, array(
      'name' => iconv ( "windows-1252" , "UTF-8" , $row->Name ),
      'is_active' => 1,
      'available_sort_by' => array('position'),
      'custom_design' => null,
      'custom_apply_to_products' => null,
      'custom_design_from' => null,
      'custom_design_to' => null,
      'custom_layout_update' => null,
      'default_sort_by' => 'position',
      'description' => iconv ( "windows-1252" , "UTF-8" , $row->Description ),
      'display_mode' => null,
      'is_anchor' => 0,
      'landing_page' => null,
      'include_in_menu' => 1,
    ),
      STORE_CODE);

    $sql = "INSERT INTO bv_x_magento_categories (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
    try{
      $mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }
    echo "Magento Category ID: " . $id;
  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>