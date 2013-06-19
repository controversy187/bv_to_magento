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

  $select_sitename = $dbh->prepare( "SELECT SettingValue FROM bvc_WebAppSetting WHERE `SettingName` = 'SiteStandardRoot'" );
  $select_sitename->execute();
  if($row = $select_sitename->fetchObject()){
    $siteRoot = $row->SettingValue;
  }
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

try {
  # MySQL with PDO_MYSQL  
  $select_product = $dbh->prepare( "SELECT * FROM bvc_Product WHERE `bvin` = :bvin_id" );
  $select_product->bindParam(':bvin_id', $bvin);
  $select_product->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_product->fetchObject()){
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_product_images', $mag_dbh)){
    $product_id = bvinToMag('bv_x_magento_products', $row->bvin, $mag_dbh);
    $imageURL = $siteRoot . ($row->ImageFileMedium == '' ? $row->ImageFileSmall : $row->ImageFileMedium);

    //Only add if the image is set in BV
    if($imageURL != $siteRoot){
      //Get the image and Base64 Encode it
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "$imageURL");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $out = curl_exec($ch);
      $mimetype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      curl_close($ch);
      $base_64_image = base64_encode($out);

      //Send the image to the API
      $file = array(
        'name'    => strtolower(preg_replace("/[^A-Za-z0-9]/", '_', $row->ProductName)),
        'content' => $base_64_image,
        'mime'    => $mimetype
      );

      include( 'api_functions.php' );

      $result = $client->catalogProductAttributeMediaCreate(
        $session,
        $product_id,
        array(
          'file'      => $file, 
          'label'     => iconv ( "windows-1252" , "UTF-8" , $row->ProductName ), 
          'position'  => '0', 
          'types'     => array(
            'image', 
            'small_image', 
            'thumbnail'
          ), 
          'exclude' => 0
        ),
        STORE_CODE
      );
    } else{
      $result = "No image found";
    }
    
    $sql = "INSERT INTO bv_x_magento_product_images (`bvin`) VALUES ( '" . $row->bvin . "');";
    try{
      $mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }
    
    echo "Result: " . $result . " for product: " . iconv ( "windows-1252" , "UTF-8" , $row->ProductName );

  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>