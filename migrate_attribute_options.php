<?php
include( 'config.php' );
include( 'custom_functions.php' );

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  //Check if the table exists, create it if it doesn't
  if(!checkTable('bv_x_magento_attributes_options', $mag_dbh)){
    $result = $mag_dbh->query('
      CREATE TABLE `bv_x_magento_attributes_options` (
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

  $result = $dbh->query('SELECT * from bvc_ProductPropertyChoice');
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

?>

<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var bvins = new Array();
var attributes = new Array();
<?php
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.

while($row = $result->fetchObject()) echo "bvins.push('" . $row->bvin . "');\n";

$mag_dbh = null;
$dbh = null;
?>
var jsonString = JSON.stringify(attributes);

$(document).ready(function(){
  totalBvins = bvins.length;

  //totalBvins = 10; //Comment this out when going live
  $('#responseBlock1').append('Adding ' + totalBvins + ' Product Attribute Options<br>');
  addAttributeOption(bvins[0], 0, totalBvins);
});

function addAttributeOption(bvin_id, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ' / ' + max + ' : ' + bvin_id + "... ");

  $.ajax({
    url: "add_attribute_options.php",
    type: "POST",
    data: {bvin : bvin_id},
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(status + " - " + msg );
    if(iteration+1 < max){
      addAttributeOption(bvins[iteration+1], iteration+1, max);
    }
  });
}
</script>
<body>
<div id="responseBlock1"></div>
</body>