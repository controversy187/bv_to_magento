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

  $select_user = $dbh->prepare( "SELECT * FROM bvc_User WHERE LOWER(`Email`) = :bvin_id ORDER BY `LastLoginDate` DESC LIMIT 1" );
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
      'firstname' => iconv ( "windows-1252" , "UTF-8" , $row->FirstName ), 
      'lastname' => iconv ( "windows-1252" , "UTF-8" , $row->LastName ), 
      'password' => sha1(uniqid(mt_rand(), true) . $row->bvin), 
      'website_id' => 1, 
      'store_id' => 1, 
      'group_id' => 1
    ));
    
    $addresses = sort_address_xml($row->BillingAddress, $row->ShippingAddress, $row->AddressBook);
    if(count($addresses) > 0)
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
	
	
	if(strlen($billingAddress) > 400){
		//because it keeps saying its utf-16... but its all utf-8
		$address = new SimpleXMLElement(preg_replace('/utf-16/', 'utf-8', $billingAddress));
		
		$billing = array(
			'city' => (string)$address->City,
			'country_id' => (string)$address->CountryName,
			'postcode' => (string)$address->PostalCode,
			'region' => (string)$address->RegionName,
			'street' => array((string)$address->Line1),
			'telephone' => (string)$address->Phone,
			'lastname' => (string)$address->LastName,
			'firstname' => (string)$address->FirstName,
			'is_default_billing' => true
		);
		if(!empty($address->Line2))
			$billing['street'][] = (string)$address->Line2;
		if(!empty($address->MiddleInitial))
			$billing['firstname'].= ' '.$address->MiddleInitial;
		if($billing['country_id'] == 'United States')
			$billing['country_id'] == 'US';
		else if($billing['country_id'] == 'Canada')
			$billing['country_id'] == 'CA';
	}
	
	if(strlen($shippingAddress) > 400) {
		$address = new SimpleXMLElement(preg_replace('/utf-16/', 'utf-8', $shippingAddress));
		if(		$billing['city'] == (string)$address->City &&
				$billing['country_id'] == (string)$address->CountryName &&
				$billing['postcode'] == (string)$address->PostalCode &&
				$billing['region'] == (string)$address->RegionName &&
				$billing['street'][0] == (string)$address->Line1 &&
				$billing['telephone'] == (string)$address->Phone &&
				$billing['lastname'] == (string)$address->LastName &&
				$billing['firstname'] == (string)$address->FirstName
				) {
			$billing['is_default_shipping'] = true;
			$addresses = array($billing);
		}
		else {
			$shipping = array(
				'city' => (string)$address->City,
				'country_id' => (string)$address->CountryName,
				'postcode' => (string)$address->PostalCode,
				'region' =>(string) $address->RegionName,
				'street' => array((string)$address->Line1),
				'telephone' => (string)$address->Phone,
				'lastname' => (string)$address->LastName,
				'firstname' => (string)$address->FirstName,
				'is_default_shipping ' => true
			);
			if(!empty($address->Line2))
				$shipping['street'][] = (string)$address->Line2;
			if(!empty($address->MiddleInitial))
				$shipping['firstname'].= ' '.(string)$address->MiddleInitial;
			$addresses = array($billing, $shipping);
		}
	}
	
	if(!empty($addressBook) && strlen($addressBook)>60) {
		$addressset = new SimpleXMLElement(preg_replace('/utf-16/', 'utf-8', $addressBook));
		foreach($addressset->children() as $address) {
			foreach($addresses as $ad)
				if(!(	$ad['city'] == (string)$address->City &&
						$ad['country_id'] == (string)$address->CountryName &&
						$ad['postcode'] == (string)$address->PostalCode &&
						$ad['region'] == (string)$address->RegionName &&
						$ad['street'][0] == (string)$address->Line1 &&
						$ad['telephone'] == (string)$address->Phone &&
						$ad['lastname'] == (string)$address->LastName &&
						$ad['firstname'] == (string)$address->FirstName
				)) {
						continue 2;  //if anything matches, discard and move on
				}
			
			$tmp = array(
					'city' => (string)$address->City,
					'country_id' => (string)$address->CountryName,
					'postcode' => (string)$address->PostalCode,
					'region' => (string)$address->RegionName,
					'street' => array((string)$address->Line1),
					'telephone' =>(string) $address->Phone,
					'lastname' => (string)$address->LastName,
					'firstname' => (string)$address->FirstName,
			);
			if(!empty($address->Line2))
				$tmp['street'][] = (string)$address->Line2;
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