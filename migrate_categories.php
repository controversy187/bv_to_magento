<?php

include('api_functions.php');

// Get BV Data
try {
 # MySQL with PDO_MYSQL  
 $source = new PDO("mysql:host=localhost;dbname=dhporg_1", 'root', ''); 
 $source->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
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

// Iterate through the categories again, modifying the Magento categories to include
// the hierarchy.



/*
// initialize magento environment for 'default' store
require_once 'app/Mage.php';
Mage::app('default'); // Default or your store view name.

//get a new category object
$category = Mage::getModel('catalog/category');
$category->setStoreId(0); // 0 = default/all store view. If you want to save data for a specific store view, replace 0 by Mage::app()->getStore()->getId().

//if update
if ($id) {
  $category->load($id);
}

$general['name'] = "Cars";
$general['path'] = "1/3"; // 1/3 is root catalog
$general['description'] = "Great new cars";
$general['meta_title'] = "Cars"; //Page title
$general['meta_keywords'] = "car, automobile";
$general['meta_description'] = "Some description to be found by meta search robots.";
$general['landing_page'] = ""; //has to be created in advance, here comes id
$general['display_mode'] = "PRODUCTS_AND_PAGE"; //static block and the products are shown on the page
$general['is_active'] = 1;
$general['is_anchor'] = 0;
$general['url_key'] = "cars";//url to be used for this category's page by magento.
$general['image'] = "cars.jpg";


$category->addData($general);

try {
    $category->save();
    echo "Success! Id: ".$category->getId();
}
catch (Exception $e){
    echo $e->getMessage();
}

*/
$source = null;
?>
