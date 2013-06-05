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

  $select_category = $dbh->prepare( "SELECT * FROM bvc_Product WHERE `bvin` = :bvin_id" );
  $select_category->bindParam(':bvin_id', $bvin);
  $select_category->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_category->fetchObject()){
  $bv_category[$row->bvin] = $row;
  
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_products', $mag_dbh)){
    //echo "<pre>";var_dump($row);die("</pre>");
    
    // Get magento set ID from BV ProductTypeID
    $attribute_set_id = bvinToMag('bv_x_magento_attribute_sets', $row->ProductTypeId, $mag_dbh);

    
    $id = $client->catalogProductCreate($session, 'simple', $attributeSet->set_id, 'product_sku', array(
        'categories' => array(2),
        'websites' => array(1),
        'name' => 'Product name',
        'description' => 'Product description',
        'short_description' => 'Product short description',
        'weight' => '10',
        'status' => '1',
        'url_key' => 'product-url-key',
        'url_path' => 'product-url-path',
        'visibility' => '4',
        'price' => '100',
        'tax_class_id' => 1,
        'meta_title' => 'Product meta title',
        'meta_keyword' => 'Product meta keyword',
        'meta_description' => 'Product meta description'
    ));

    var_dump ($id);

    $sql = "INSERT INTO bv_x_magento_categories (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
    try{
      //Uncomment when doing a live insert
      //$mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }
    //echo "Magento Category ID: " . $id;
  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>