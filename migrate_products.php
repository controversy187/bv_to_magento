<?php
include( 'config.php' );
include( 'api_functions.php' );
include( 'custom_functions.php' );

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  //Check if the table exists, create it if it doesn't
  if(!checkTable('bv_x_magento_products', $mag_dbh)){
    $result = $mag_dbh->query('
      CREATE TABLE `bv_x_magento_products` (
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

  $result = $dbh->query('SELECT * from bvc_Product');
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}
?>

<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var bvins = new Array();

<?php
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.

while($row = $result->fetchObject()) echo "bvins.push('" . $row->bvin . "');\n";

$mag_dbh = null;
$dbh = null;
?>
$(document).ready(function(){

  totalBvins = bvins.length;
  $('#responseBlock1').append('Adding ' + totalBvins + ' bvins<br>');

  addProduct(bvins[0], 0, totalBvins);
});

function addProduct(bvin_id, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ': ' + bvin_id + "... ");

  $.ajax({
    url: "add_product.php",
    type: "POST",
    data: {bvin : bvin_id},
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(status + " - " + msg );
    if(iteration+1 < max){
      addProduct(bvins[iteration+1], iteration+1, max);
    }
  });
}
</script>
<body>
<div id="responseBlock1"></div>

</body>
</html>