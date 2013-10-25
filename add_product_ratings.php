<?php
/*
 *  DISCLAIMER
 *  This script will import "star" ratings to Magento reviews. However, if descriptions are written
 *  similar to each other, they will collide and not be imported. If special characters are used in
 *  the description, there is a chance the script will not translate them properly and will not
 *  import the rating.
 */

include( 'config.php' );
include( 'custom_functions.php' );

$startTime = time();

$store_id_db_mapping = array(
  2 => 'canada_1', // DHP CA
  3 => 'dod_1', // DOD
  4 => 'dhporg_1', // DHP US
);

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  $reviews = $mag_dbh->prepare( "
    SELECT *
    FROM discoveryhousepublishers.review r 
    INNER JOIN review_detail rd ON r.review_id = rd.review_id
    INNER JOIN catalog_product_entity cpe ON r.entity_pk_value = cpe.entity_id
    ORDER BY sku;
  " );
  $reviews->execute();
}  
catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

// Establish BV Connections
try {
  # MySQL with PDO_MYSQL  
  $canada_1 = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=canada_1", SRC_DB_USER, SRC_DB_PW); 
  $canada_1->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
  $dod_1 = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=dod_1", SRC_DB_USER, SRC_DB_PW); 
  $dod_1->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
  $dhporg_1 = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=dhporg_1", SRC_DB_USER, SRC_DB_PW); 
  $dhporg_1->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

$found = 0;
$notFound = 0;
while($row = $reviews->fetchObject()){
  $databaseConn = $store_id_db_mapping[$row->store_id];
  $description = $row->detail;
  $description = substr($description, 0,45) . "%";
  $description = str_replace("--", "%", $description);
  $description = str_replace("'",  "%", $description);
  $description = str_replace("-",  "%", $description);

  $bv_review_count = $$databaseConn->prepare( "
    SELECT count(*) AS count 
    FROM bvc_ProductReview PR 
    INNER JOIN bvc_Product P ON P.bvin = PR.ProductBvin
    WHERE PR.Description LIKE :review_description
  " );
  $bv_review_count->bindParam(':review_description', $description);
  $bv_review_count->execute();

  $bv_review = $$databaseConn->prepare( "
    SELECT * 
    FROM bvc_ProductReview PR 
    INNER JOIN bvc_Product P ON P.bvin = PR.ProductBvin
    WHERE PR.Description LIKE :review_description
  " );
  $bv_review->bindParam(':review_description', $description);
  $bv_review->execute();
  if($bv_review_row = $bv_review->fetchObject()){
    $count = $bv_review_count->fetchObject();
    if($count->count > 1){
      echo "<pre>";var_dump("DUPE " . $databaseConn . " " . $row->sku . " " . $description . ": " . $count->count );echo("</pre>");
    } else {
      $data = array(
        'option_id' => $bv_review_row->Rating,
        'value' => $bv_review_row->Rating,
        'remote_ip' => '127.0.0.1',
        'remote_ip_long' => 2130706433,
        'entity_pk_value' => $row->entity_pk_value,
        'percent' => ($bv_review_row->Rating / 5) * 100,
        'rating_id' => 1,
        'review_id' => $row->review_id
      );
      $add_rating = $mag_dbh->prepare( "
        INSERT INTO rating_option_vote 
        (option_id, value, remote_ip, remote_ip_long, entity_pk_value, rating_id, review_id, percent)
        VALUES ( " . $bv_review_row->Rating . "," . $bv_review_row->Rating . ", '127.0.0.1', 2130706433, " . $row->entity_pk_value . ", 1, " . $row->review_id .", " . ($bv_review_row->Rating / 5) * 100 ." )
      " );
      $add_rating->execute();
    }
    $found++;
  } else {
    $notFound++;
    echo "<pre>";var_dump($notFound . " " . $databaseConn . " " . $row->sku . " " . $description);echo("</pre>");
  }
}

echo "<pre>";var_dump('Found: '.$found, 'Not Found: '.$notFound);die("</pre>");