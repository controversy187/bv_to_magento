<?php
include( 'config.php' );
include( 'api_functions.php' );
include( 'custom_functions.php' );

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

  $result = $dbh->query('SELECT * from bvc_ProductTypeXProductProperty');
} catch(PDOException $e) {  
  echo $e->getMessage();
  exit();
}

?>

<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var bvins = new Array();
var set_bvins = new Array();

<?php
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.

while($row = $result->fetchObject()){
  echo "bvins.push('" . $row->ProductPropertyBvin . "');\n";
  echo "set_bvins.push('" . $row->ProductTypeBvin . "');\n";
}

$mag_dbh = null;
$dbh = null;
?>
$(document).ready(function(){

  totalBvins = bvins.length;
  $('#responseBlock1').append('Mapping ' + totalBvins + ' Product Attributes<br>');

  addAttribute(bvins[0], set_bvins[0], 0, totalBvins);
});

function addAttribute(bvin_id, set_id, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ' / ' + max + ' : ' + bvin_id + "... ");

  $.ajax({
    url: "add_attribute_to_set.php",
    type: "POST",
    data: {
      bvin : bvin_id,
      set_bvin : set_id
    },
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(status + " - " + msg );
    if(iteration+1 < max){
      addAttribute(bvins[iteration+1], set_bvins[iteration+1], iteration+1, max);
    }
  });
}
</script>
<body>
<div id="responseBlock1"></div>
</body>