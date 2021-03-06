<?php
include( 'config.php' );
include( 'custom_functions.php' );

$startTime = time();

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
  // Check if we already imported this Bvin
  if(!checkBvinExists($row->bvin, 'bv_x_magento_products', $mag_dbh)){
    $category_bvins   = getBVCategoryFromProductBvin($row->bvin, $dbh);
    $category_ids     = bvCategoriesToMagentoCategoryIds($category_bvins, $mag_dbh);

    // Get magento set ID from BV ProductTypeID
    $name                   = strip_tags(iconv ( "windows-1252" , "UTF-8" , $row->ProductName ));
    $attribute_set_id       = ($row->ProductTypeId == "" ? DEFAULT_ATTRIBUTE_SET_ID : bvinToMag('bv_x_magento_attribute_sets', $row->ProductTypeId, $mag_dbh));
    $status                 = ($row->Status == "1" ? 1 : 2);        //In magento, 1 = active, 2 = inactive
    $tax_class              = ($row->TaxExempt == "1" ? 0 : 2);  //0 = none, 2 = taxable goods
    $meta_title             = ($row->MetaTitle == "" ? iconv ( "windows-1252" , "UTF-8" , $row->ProductName ) : iconv ( "windows-1252" , "UTF-8" , $row->MetaTitle ));
    //If we don't have a long description, use the short description as well
    $longDesc               = ($row->LongDescription == "" ? iconv ( "windows-1252" , "UTF-8" , $row->ShortDescription ) : iconv ( "windows-1252" , "UTF-8" , $row->LongDescription ));
    //If we don't have a short description, use a truncated long description. That looks messy.
    $shortDesc              = ($row->ShortDescription == "" ? (strlen($longDesc) > 125 ? substr($longDesc, 0, 125) . "... " : substr($longDesc, 0, 125)) : iconv ( "windows-1252" , "UTF-8" , $row->ShortDescription ));
    $additional_attributes['single_data']  = getAdditionalAttributes($row->bvin, $dbh, $mag_dbh);
    $additional_attributes['single_data'][]  = array('key' => 'msrp', 'value' => $row->ListPrice);

    $dataArray = array(
      'categories'            => $category_ids,
      'websites'              => array(WEBSITE_ID),
      'name'                  => $name,
      'description'           => $longDesc,
      'short_description'     => $shortDesc,
      'weight'                => $row->ShippingWeight,
      'status'                => $status,
      'url_key'               => strtolower($row->SKU . ' '),
      'url_path'              => strtolower($row->SKU . '.html'),
      //'visibility'          => '4',
      'price'                 => $row->SitePrice,
      'tax_class_id'          => $tax_class ,
      'meta_title'            => $meta_title,
      'meta_keyword'          => iconv ( "windows-1252" , "UTF-8" , $row->MetaKeywords ),
      'meta_description'      => iconv ( "windows-1252" , "UTF-8" , $row->MetaDescription ),
      'additional_attributes' => $additional_attributes
    );
    
    include( 'api_functions.php' );
    
    try{
      $id = $client->catalogProductCreate($session, 'simple', $attribute_set_id, $row->SKU, $dataArray, STORE_CODE );
    } catch (SoapFault $e) {
      if($e->faultstring == 'The value of attribute "SKU" must be unique'){  // Need to add the sku to the store
        //Get websites from old product and add our new website
        $result = $client->catalogProductInfo($session, $row->SKU . ' ', STORE_CODE);
        $id = $result->product_id;
        $websites = $result->websites;
        $websites[] = WEBSITE_ID;
        $dataArray['categories'] = array_merge($category_ids, $result->categories);
        //echo "<pre>";var_dump($category_ids, $result->categories, $dataArray);die("</pre>");
        
        //Add this site to old product
        $result = $client->catalogProductUpdate($session, $row->SKU . ' ', array('websites' => $websites));

        //Update this product with new information
        unset($dataArray['websites']); // Only deal with our specific Store, don't update all websites
        $result = $client->catalogProductUpdate($session, $row->SKU . ' ', $dataArray, STORE_CODE);
        echo "Existing product added to store - SKU: " . $row->SKU . " - " . $name . ' - ';
      } else{
        echo "<pre>";var_dump($e->faultstring, $dataArray);die("</pre>");
      }
    } 



    $sql = "INSERT INTO bv_x_magento_products (`bvin`, `mag_id`) VALUES ( '" . $row->bvin . "', " . $id ." );";
    try{
      $mag_dbh->query($sql);
    } catch(PDOException $e) {  
      echo $e->getMessage();
      exit();
    }
    $timePassed = time() - $startTime;
    if($id) echo "   Magento Product '" . $name . "' ID: " . $id . " - (" . $timePassed . " seconds total)";
  } else {
    echo "Record already added";
  }
} else {
  echo "Error: bvin $bvin not found";
}


$mag_dbh = null;
$dbh = null;
?>