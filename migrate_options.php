<?php
include( 'config.php' );
include( 'custom_functions.php' );

//*
//This section is a little strange, based on how Magento handles option creation.
//BV creates a single Choice, then maps it to many products. Magento creates a
//Single option mapped to a single Product. So a BV Choice needs to be replicated
//onto many Magento Products as Options.
//The overview is to iterate through the BV table bvc_ProductXChoice, which maps
//products to Choices. With the Product and the Choice, we can create an Option in
//Magento using our mapped Product ID (created in a previous step).

// Establish connection to Magento DB
try {
  # MySQL with PDO_MYSQL  
  $mag_dbh = new PDO("mysql:host=" . MAG_DB_HOST . ";dbname=". MAG_DB_NAME, MAG_DB_USER, MAG_DB_PW); 
  $mag_dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

  //Check if the table exists, create it if it doesn't
  if(!checkTable('bv_x_magento_products_options', $mag_dbh)){
    $result = $mag_dbh->query('
      CREATE TABLE `bv_x_magento_products_options` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_bvin` varchar(45) DEFAULT NULL,
        `choice_bvin` varchar(45) DEFAULT NULL,
        `product_mag_id` int(10) DEFAULT NULL,
        `option_mag_id` int(10) DEFAULT NULL,
        PRIMARY KEY (`id`)
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

  $result = $dbh->query('SELECT * from bvc_ProductXChoice');
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

?>

<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var product_bvins = new Array();
var choice_bvins = new Array();

<?php
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.

while($row = $result->fetchObject()){
  echo "product_bvins.push('" . $row->ProductId . "');\n";
  echo "choice_bvins.push('"  . $row->ChoiceId  . "');\n";
}

$mag_dbh = null;
$dbh = null;
?>
$(document).ready(function(){

  totalBvins = product_bvins.length;
  $('#responseBlock1').append('Adding ' + totalBvins + ' Product Options<br>');

  addOption(product_bvins[0], choice_bvins[0], 0, totalBvins);
});

function addOption(product_bvin_id, choice_bvin_id, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ' / ' + max + ' : ' + product_bvin_id + '... ');

  $.ajax({
    url: "add_option.php",
    type: "POST",
    data: {
      product_bvin_id : product_bvin_id,
      choice_bvin_id  : choice_bvin_id
    },
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(status + " - " + msg );
    if(iteration+1 < max){
      addOption(product_bvins[iteration+1], choice_bvins[iteration+1], iteration+1, max);
    }
  });
}
</script>
<body>
<div id="responseBlock1"></div>
</body>