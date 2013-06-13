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

  $select_user = $dbh->prepare( "SELECT * FROM bvc_User WHERE `Email` = :bvin_id ORDER BY `LastLoginDate` DESC LIMIT 1" );
  $select_user->bindParam(':bvin_id', $bvin);
  $select_user->execute();
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

if($row = $select_user->fetchObject()){

  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_users', $mag_dbh)){
    
    $id = $client->customerCustomerCreate($session, array(
      'email' => $row->Email,
      'firstname' => $row->FirstName, 
      'lastname' => $row->LastName, 
      'password' => sha1(uniqid(mt_rand(), true) . $row->bvin), 
      'website_id' => 1, 
      'store_id' => 1, 
      'group_id' => 1
    ));

    $sql = "INSERT INTO bv_x_magento_users (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
    try{
      $mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }
    echo "Magento User ID: " . $id;
  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>