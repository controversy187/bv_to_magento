<?php
require 'config.php';
require( 'custom_functions.php' );

$bv = mysql_connect(SRC_DB_HOST, SRC_DB_USER, SRC_DB_PW);
if(!$bv) {
	echo mysql_error(), "\r\n";
	exit;
}
mysql_set_charset('utf8', $bv);
mysql_select_db(SRC_DB_NAME, $bv);

try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW, array(PDO::ATTR_PERSISTENT => true)); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
}  
catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

$sql = 'SELECT u.Email, r.Description, r.ReviewDate, r.UserID, r.ProductBvin FROM `bvc_ProductReview` as r LEFT JOIN `bvc_User` as u ON u.bvin=r.UserID WHERE r.Approved=1;';
$set = mysql_unbuffered_query($sql);

if(!$set || mysql_errno()){
	echo "Failed: \r\n";
	echo mysql_error();
	echo "\r\n";
	exit;
}

while($row = mysql_fetch_assoc($set)){
	
	if($row['UserID'] == '0' || empty($row['Email'])){
		$user_id = NULL;
	} else {
		$user_id = bvinToMag('bv_x_magento_users', $row['Email'], $mag_dbh);
	}
	
	$product_id = bvinToMag('bv_x_magento_products', $row['ProductBvin'], $mag_dbh);
	
	
	$sql = sprintf("INSERT INTO `review` (`review_id`, `created_at`, `entity_id`, `entity_pk_value`, `status_id`) VALUES(NULL, '%s', 1, %d, 1)", 
			mysql_real_escape_string($row['ReviewDate']), $product_id);
	try{
		echo "<pre>";var_dump($sql);echo("</pre>");
		$mag_dbh->query($sql);
		if($mag_dbh->errorCode() != '00000'){
			echo '<p><b>', $mag_dbh->errorInfo(), "</b><br />\nThat didn't work! </p>";
			continue;
		}
		$review_id = $mag_dbh->lastInsertId();
		
		$sql = sprintf("INSERT INTO `review_detail` (`detail_id`,`review_id`,`store_id`,`title`,`detail`,`nickname`,`customer_id`) VALUES(NULL, %d, %d, 'Review', '%s', '', %s)",
				$review_id,
				intval(STORE_ID),
				
				mysql_real_escape_string($row['Description']),
				(is_null($user_id))?'NULL':intval($user_id)
			);
		echo "<pre>";var_dump($sql);echo("</pre>");
		$mag_dbh->query($sql);
		if($mag_dbh->errorCode() != '00000'){
			echo '<p><b>', $mag_dbh->errorInfo(), "</b><br />\nThe user ID was: ", (is_null($user_id))?'NULL':intval($user_id), ' from ', $row['Email'], '</p>'; 
		}
		
		$sql = sprintf("INSERT INTO `%s` (`review_id`, `store_id`) VALUES (%d, 0), (%d, %d)",
			'review_store', $review_id, $review_id, STORE_ID);
		$mag_dbh->query($sql);
	} catch(PDOException $e) {
		echo $e->getMessage();
		echo ", on query: ", $sql, "\r\n";
		exit();
	}
}

