<?php
include( 'config.php' );
include( 'api_functions.php' );
include( 'custom_functions.php' );

// Get BV Data
try {
 # MySQL with PDO_MYSQL  
 $source = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=". SRC_DB_NAME, SRC_DB_USER, SRC_DB_PW); 
 $source->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

 //Check if the table exists, create it if it doesn't
 if(!checkTable('bv_x_magento_categories', $source)){
  

 }

 $result = $source->query('SELECT * from bvc_Category');  
 $result->setFetchMode( PDO::FETCH_OBJ );
}  
catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

$category = array();
echo "<table>";
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.
while($row = $result->fetch()) {
  //echo "<pre>";var_dump($row);die("</pre>");
  $id = $client->catalogCategoryCreate($session, 2, array(
    'name' => iconv ( mb_detect_encoding($row->Name) , "UTF-8" , $row->Name ),
    'is_active' => 1,
    'available_sort_by' => array('position'),
    'custom_design' => null,
    'custom_apply_to_products' => null,
    'custom_design_from' => null,
    'custom_design_to' => null,
    'custom_layout_update' => null,
    'default_sort_by' => 'position',
    'description' => iconv ( mb_detect_encoding($row->Description) , "UTF-8" , $row->Description ),
    'display_mode' => null,
    'is_anchor' => 0,
    'landing_page' => null,
    'include_in_menu' => 1,
  ));
  echo "<tr><td>" . $id . "</td><td>" . $row->Name . "</td><td>" . iconv ( mb_detect_encoding($row->Name) , "UTF-8" , $row->Name ) . "</td><td>" . $row->Description . "</td><td>" . iconv ( mb_detect_encoding($row->Description) , "UTF-8" , $row->Description ) . "</td></tr>";
  //echo "<pre>";var_dump($id);echo("</pre>");
}

echo "</table>";

// Iterate through the categories again, modifying the Magento categories to include
// the hierarchy.



$source = null;
?>
