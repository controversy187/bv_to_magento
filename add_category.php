<?php
var_dump($_POST);
/*
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
*/
?>