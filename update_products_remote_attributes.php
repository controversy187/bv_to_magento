<?php
include( 'config.php' );
include( 'custom_functions.php' );
include( 'api_functions.php' );

$result = $client->catalogProductList($session);
?>

<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var productSKUs = new Array();

<?php
// Create all the categories in a non-hiearchy. Store the IDs in the DB for later use
// and store them in an array for use later in this code.


foreach ($result as $product) {
  echo "productSKUs.push('" . $product->sku . "');\n";
}
?>
$(document).ready(function(){

  totalSKUs = productSKUs.length;
  $('#responseBlock1').append('Adding ' + totalSKUs + ' bvins<br>');

  //totalSKUs = 1; // Delete this when going live. Only limit to 10 bvins for development
  updateProduct(productSKUs[0], 0, totalSKUs);
});

function updateProduct(productSKU, iteration, max){
  humanNumber = iteration+1;
  $('#responseBlock1').append('<br>' + humanNumber + ' / ' + max + ' : ' + productSKU + '... ');

  $.ajax({
    url: "update_product_remote_attributes.php",
    type: "POST",
    data: {sku : productSKU },
    dataType: "html"
  }).done(function(msg, status) {
    $('#responseBlock1').append(status + " - " + msg );
    if(iteration+1 < max){
      updateProduct(productSKUs[iteration+1], iteration+1, max);
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