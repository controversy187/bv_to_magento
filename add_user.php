<?php
include( 'config.php' );
include( 'api_functions.php' );
include( 'custom_functions.php' );

$bvin = $_POST['bvin'];

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW, array(PDO::ATTR_PERSISTENT => true)); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
}  
catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}


// Get BV Data
try {
  # MySQL with PDO_MYSQL  
  $dbh = new PDO("mysql:host=" . SRC_DB_HOST . ";dbname=". SRC_DB_NAME, SRC_DB_USER, SRC_DB_PW, array(PDO::ATTR_PERSISTENT => true)); 
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
  if(!checkBvinExists($row->Email, 'bv_x_magento_users', $mag_dbh)){
  	
    if(empty($row->PasswordHint))
    	$row->PasswordHint = md5($row->Salt);
    
    $id = $client->customerCustomerCreate($session, array(
      'email' => $row->Email,
      'firstname' => $row->FirstName, 
      'lastname' => $row->LastName, 
      'password' =>$row->PasswordHint, 
      'website_id' => 1, 
      'store_id' => 1, 
      'group_id' => 1
    ));
    
    $addresses = sort_address_xml($row->BillingAddress, $row->ShippingAddress, $row->AddressBook);
    foreach ($addresses as $addy){
    	$result = $client->customerAddressCreate($session, $id, $addy);
    }

    $sql = "INSERT INTO bv_x_magento_users (`bvin`, `mag_id`) VALUES ( '" . $row->Email . "', " . $id ." );";
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


function sort_address_xml($billingAddress, $shippingAddress, $addressBook) {
	$addresses = array();
	
	//because it keeps saying its utf-16... but its all utf-8
	$address = new SimpleXMLElement(preg_replace('/utf-16/', 'utf-8', $billingAddress));
	
	$billing = array(
		'city' => $address->City,
		'country_id' => $address->CountryName,
		'postcode' => $address->PostalCode,
		'region' => $address->RegionName,
		'street' => array($address->Line1),
		'telephone' => $address->Phone,
		'lastname' => $address->LastName,
		'firstname' => $address->FirstName,
		'is_default_billing' => true
	);
	if(!empty($address->Line2))
		$billing['street'][] = $address->Line2;
	if(!empty($address->MiddleInitial))
		$billing['firstname'].= ' '.$address->MiddleInitial;
	
	
	$address = new SimpleXMLElement(preg_replace('/utf-16/', 'utf-8', $shippingAddress));
	if(		$billing['city'] == $address->City &&
			$billing['country_id'] == $address->CountryName &&
			$billing['postcode'] == $address->PostalCode &&
			$billing['region'] == $address->RegionName &&
			$billing['street'][0] == $address->Line1 &&
			$billing['telephone'] == $address->Phone &&
			$billing['lastname'] == $address->LastName &&
			$billing['firstname'] == $address->FirstName
			) {
		$billing['is_default_shipping'] = true;
		$addresses = array($billing);
	}
	else {
		$shipping = array(
			'city' => $address->City,
			'country_id' => $address->CountryName,
			'postcode' => $address->PostalCode,
			'region' => $address->RegionName,
			'street' => array($address->Line1),
			'telephone' => $address->Phone,
			'lastname' => $address->LastName,
			'firstname' => $address->FirstName,
			'is_default_shipping ' => true
		);
		if(!empty($address->Line2))
			$shipping['street'][] = $address->Line2;
		if(!empty($address->MiddleInitial))
			$shipping['firstname'].= ' '.$address->MiddleInitial;
		$addresses = array($billing, $shipping);
	}
	
	if(!empty($addressBook) && strlen($addressBook)>60) {
		$addressset = new SimpleXMLElement(preg_replace('/utf-16/', 'utf-8', $addressBook));
		foreach($addressset->children() as $address) {
			foreach($addresses as $ad)
				if(!(	$ad['city'] == $address->City &&
						$ad['country_id'] == $address->CountryName &&
						$ad['postcode'] == $address->PostalCode &&
						$ad['region'] == $address->RegionName &&
						$ad['street'][0] == $address->Line1 &&
						$ad['telephone'] == $address->Phone &&
						$ad['lastname'] == $address->LastName &&
						$ad['firstname'] == $address->FirstName
				)) {
						continue 2;  //if anything matches, discard and move on
				}
			
			$tmp = array(
					'city' => $address->City,
					'country_id' => $address->CountryName,
					'postcode' => $address->PostalCode,
					'region' => $address->RegionName,
					'street' => array($address->Line1),
					'telephone' => $address->Phone,
					'lastname' => $address->LastName,
					'firstname' => $address->FirstName,
			);
			if(!empty($address->Line2))
				$tmp['street'][] = $address->Line2;
			if(!empty($address->MiddleInitial))
				$tmp['firstname'].= ' '.$address->MiddleInitial;
			$addresses[] = $tmp;
			
		}
	}
	
	return $addresses;
}


$mag_dbh = null;
$dbh = null;
?>