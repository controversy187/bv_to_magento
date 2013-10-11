<?php include( 'config.php' ); ?>
<pre>
01. <a href="migrate_categories.php?<?php echo ITERATION; ?>" target="_blank">Import Categories</a><br>
02. <a href="migrate_categories_hierarchy.php?<?php echo ITERATION; ?>" target="_blank">Organize Categories Into Hierarchy</a><br>
03. <a href="migrate_attribute_sets.php?<?php echo ITERATION; ?>" target="_blank">Create Attriubte Sets</a> (Groups for Attributes)<br>
04. <a href="migrate_attributes.php?<?php echo ITERATION; ?>" target="_blank">Import Attributes</a> (Admin controlled properties of products)<br>
05. <a href="migrate_attribute_options.php?<?php echo ITERATION; ?>" target="_blank">Import Attribute Options</a> (Admin controlled properties of products)<br>
06. <a href="assign_attributes_to_sets.php?<?php echo ITERATION; ?>" target="_blank">Assign Attributes to Sets</a> (Admin controlled properties of products)<br>
07. <a href="migrate_products.php?<?php echo ITERATION; ?>" target="_blank">Create Products</a><br>
08. <a href="migrate_product_images.php?<?php echo ITERATION; ?>" target="_blank">Import Product Images</a><br>
09. <a href="migrate_users.php?<?php echo ITERATION; ?>" target="_blank">Create Users</a><br>
10. <a href="reviews.php?<?php echo ITERATION; ?>" target="_blank">Create Product Reviews</a><br>
<br>
<a href="update_products.php?<?php echo ITERATION; ?>" target="_blank">Update Products - set url_key and url_path as lowercase SKU</a><br>
<a href="update_products_keywords.php?<?php echo ITERATION; ?>" target="_blank">Update Products - Add BV Keywords to current Magento keywords</a><br>
<a href="update_products_remote_attributes.php?<?php echo ITERATION; ?>" target="_blank">Update Products - Set Attributes ISBN-10, UPC, Trim Size, Carton QTY to NULL</a><br>
<a href="migrate_update_prices.php?<?php echo ITERATION; ?>" target="_blank">Update Product Prices (CSV)</a><br>
</pre>