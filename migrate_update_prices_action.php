<?php
include( 'config.php' );
include( 'custom_functions.php' );
# include parseCSV class.
require_once('parsecsv-0.4.3-beta/parsecsv.lib.php');

if ($_FILES["csv_upload"]["type"] == "text/csv") { 
  if ($_FILES["csv_upload"]["error"] > 0) {
    echo "Return Code: " . $_FILES["csv_upload"]["error"];
    die("<br />");
  } else {
    $csv = new parseCSV($_FILES["csv_upload"]["tmp_name"]);
  }
} else { 
  $theresanerror = true;
  die("Some kind of error... Probably not a CSV file");
}

?>

<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var skus = new Array();
var prices = new Array();
<?php
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.

foreach($csv->data as $data){
  echo "skus.push('" . $data['ProdCode'] . "');\n";
  echo "prices.push('" . $data['Price'] . "');\n";
}
?>

$(document).ready(function(){

  totalSkus = skus.length;
  $('#responseBlock1').append('Updating ' + totalSkus + ' SKUs<br>');

  //totalSkus = 10; // Delete this when going live. Only limit to 10 skus for development
  updatePrice(skus[0], prices[0], 0, totalSkus);
});

function updatePrice(sku, price, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ' / ' + max + ' : ' + sku + '... ');

  $.ajax({
    url: "update_prices.php",
    type: "POST",
    data: {
      sku   : sku,
      price : price
    },
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(status + " - " + msg );
    if(iteration+1 < max){
      updatePrice(skus[iteration+1], prices[iteration+1], iteration+1, max);
    }
  });
}
</script>
<body>
<pre>
<div id="responseBlock1"></div>

</pre>
</body>
</html>