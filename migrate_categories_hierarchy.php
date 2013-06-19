<?php
include( 'config.php' );
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

// Iterate through the categories, modifying the Magento categories to include
// the hierarchy.

$bvin_to_mag = array();
$mag_to_bvin = array();

// First, get the ID mappings
$sql = "SELECT * FROM bv_x_magento_categories";
$result = $mag_dbh->query($sql);
?>
<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var bvins = new Array();
var mag_ids = new Array();
<?php

while($row = $result->fetchObject()){
  echo "bvins.push('" . $row->bvin . "');\n";
  echo "mag_ids.push('" . $row->mag_id . "');\n";
}

/*
//Loop through our new entries and update the Categories using the Magento API
foreach($bvin_to_mag as $bvin => $mag_id){
  $result = $dbh->query('SELECT * from bvc_Category WHERE bvin = \'' . $bvin . '\'');
  if($row = $result->fetchObject()){
    if($row->ParentID != 0){
      $return = $client->catalogCategoryMove($session, $mag_id, $bvin_to_mag[$row->ParentID]);
    }
  }
}
*/

$mag_dbh = null;
$dbh = null;
?>
$(document).ready(function(){
  totalBvins = bvins.length;
  $('#responseBlock1').append('Moving ' + totalBvins + ' categories<br>');
  moveCategory(bvins[0], mag_ids[0], 0, totalBvins);
});

function moveCategory(bvin_id, mag_id, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ' / ' + max + ' : ' + mag_id + "... ");

  $.ajax({
    url: "move_category.php",
    type: "POST",
    data: {
      bvin : bvin_id,
      mag_id: mag_id
    },
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(msg);
    if(iteration+1 < max){
      moveCategory(bvins[iteration+1], mag_ids[iteration+1], iteration+1, max);
    }
  });
}
</script>
<body>
<div id="responseBlock1"></div>
</body>
</html>
