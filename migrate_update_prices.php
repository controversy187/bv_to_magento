<?php
include( 'config.php' );
include( 'custom_functions.php' );
?>


<html>
<head>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script>
var bvins = new Array();

</script>
</head>
<body>
<form id="frm_csv_upload" name="frm_csv_upload" method="POST" action="migrate_update_prices_action.php">
<input type="file" name="csv_upload" id="csv_upload" /><br>
<input type="submit">
</form>
</body>
</html>