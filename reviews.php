<?php
require 'config.php';


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

$sql = 'SELECT u.Email, r.Description, r.ReviewDate, r.UserID, r.ProductBvin FROM `bvc_productreview` as r LEFT JOIN `bvc_user` as u ON u.bvin=r.UserID WHERE r.Approved=1 LIMIT 20;';
$set = mysql_unbuffered_query($sql);

if(!$set || mysql_errno()){
	echo "Failed: \r\n";
	echo mysql_error();
	echo "\r\n";
	exit;
}

for($i=0; $i<5; $i++){
	$row = mysql_fetch_assoc($set);
	
	if($row['UserID'] == '0' || empty($row['Email'])){
		$user_id = NULL;
	} else {
		$user_id = 2;//bvinToMag('bv_x_magento_users', $row['Email'], $mag_dbh);
	}
	
	$product_id = 15; //bvinToMag('bv_x_magento_products', $row['ProductBvin'], $mag_dbh);
	
	$store_id = 3; //????
	
	$sql = sprintf("INSERT INTO `review` (`review_id`, `created_at`, `entity_id`, `entity_pk_value`, `status_id`) VALUES(NULL, '%s', 1, %d, 1)", 
			mysql_real_escape_string($row['ReviewDate']), $product_id);
	try{
		$mag_dbh->query($sql);
		$review_id = $mag_dbh->lastInsertId();
		
		$sql = sprintf("INSERT INTO `review_detail` (`detail_id`,`review_id`,`store_id`,`title`,`detail`,`nickname`,`customer_id`) VALUES(NULL, %d, %d, 'Review', '%s', '', %s)",
				$review_id,
				intval($store_id),
				
				mysql_real_escape_string($row['Description']),
				(is_null($user_id))?'NULL':intval($user_id)
			);
		$mag_dbh->query($sql);
	} catch(PDOException $e) {
		echo $e->getMessage();
		echo ", on query: ", $sql, "\r\n";
		exit();
	}
}

