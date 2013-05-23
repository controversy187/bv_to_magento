<?php
include( 'config.php' );
include( 'api_functions.php' );
include( 'custom_functions.php' );

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  //Check if the table exists, create it if it doesn't
  if(!checkTable('bv_x_magento_categories', $mag_dbh)){
    $result = $mag_dbh->query('
      CREATE TABLE `bv_x_magento_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bvin` varchar(45) DEFAULT NULL,
        `mag_id` int(10) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `bvin_UNIQUE` (`bvin`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ');
  }
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

  $result = $dbh->query('SELECT * from bvc_Category');
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

$category = array();
$bv_category = array();
$new_records = 0;
$skipped_records = 0;
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.
while($row = $result->fetchObject()) {
  $bv_category[$row->bvin] = $row;
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_categories', $mag_dbh)){

    // Create the Category
    $id = $client->catalogCategoryCreate($session, 2, array(
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
    ));

    // Add record to Array
    $category[] = array(
      'bvin' => $row->bvin,
      'mag_id' => $id,
      'name' => $row->Name
    );

    $new_records++;
  } else {
    $skipped_records++;
  }
}

// Insert the new records into the DB.
if( !empty($category)){
  $sql = "INSERT INTO bv_x_magento_categories (`bvin`, `mag_id`) VALUES ";
  foreach($category as $ids){
    $sql .= " ( '" . $ids['bvin'] . "', " . $ids['mag_id'] . " ),";
  }
  $sql = substr($sql, 0, -1) . ";";  

  try{
    $mag_dbh->query($sql);
  } catch(PDOException $e) {  
    echo $e->getMessage();
    exit();
  }
}

echo "New categories: " . $new_records . "<br>";
echo "Skipped categories: " . $skipped_records . "<br>";

// Iterate through the categories again, modifying the Magento categories to include
// the hierarchy.

$bvin_to_mag = array();
$mag_to_bvin = array();

// First, get the ID mappings
$sql = "SELECT * FROM bv_x_magento_categories";
$result = $mag_dbh->query($sql);

while($row = $result->fetchObject()){
  $bvin_to_mag[$row->bvin] = $row->mag_id;
  $mag_to_bvin[$row->mag_id] = $row->bvin;
}

//Loop through our new entries and update the Categories using the Magento API
foreach($bvin_to_mag as $bvin => $mag_id){
  $result = $dbh->query('SELECT * from bvc_Category WHERE bvin = \'' . $bvin . '\'');
  if($row = $result->fetchObject()){
    if($row->ParentID != 0){
      //$mag_parent = $bvin_to_mag[$row->ParentID];
      $return = $client->catalogCategoryMove($session, $mag_id, $bvin_to_mag[$row->ParentID]);
    }
  }
}


$mag_dbh = null;
$dbh = null;
?>
