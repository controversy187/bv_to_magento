<?php

function checkTable($table, $dbh){
	return $dbh->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
}

function checkBvinExists($bvin, $table, $dbh){
	return $dbh->query("SELECT count(*) FROM $table WHERE `bvin` = '$bvin'")->fetchColumn() > 0;
}

<?php
/**
 * Create an atribute-set.
 *
 * For reference, see Mage_Adminhtml_Catalog_Product_SetController::saveAction().
 *
 * @return array|false
 */
function createAttributeSet($setName, $copyGroupsFromID = -1) {
  $setName = trim($setName);
  $this->logInfo("Creating attribute-set with name [$setName].");
  if($setName == '') {
    $this->logError("Could not create attribute set with an empty name.");
    return false;
  }

  //>>>> Create an incomplete version of the desired set.
  $model = Mage::getModel('eav/entity_attribute_set');

  // Set the entity type.
  $entityTypeID = Mage::getModel('catalog/product')->getResource()->getTypeId();
  $this->logInfo("Using entity-type-ID ($entityTypeID).");

  $model->setEntityTypeId($entityTypeID);

  // We don't currently support groups, or more than one level. See
  // Mage_Adminhtml_Catalog_Product_SetController::saveAction().
  $this->logInfo("Creating vanilla attribute-set with name [$setName].");
  $model->setAttributeSetName($setName);

  // We suspect that this isn't really necessary since we're just
  // initializing new sets with a name and nothing else, but we do
  // this for the purpose of completeness, and of prevention if we
  // should expand in the future.
  $model->validate();

  // Create the record.
  try{
    $model->save();
  } catch(Exception $ex) {
    $this->logError("Initial attribute-set with name [$setName] could not be saved: " . $ex->getMessage());
    return false;
  }

  if(($id = $model->getId()) == false) {
    $this->logError("Could not get ID from new vanilla attribute-set with name [$setName].");
    return false;
  }

  $this->logInfo("Set ($id) created.");

  //<<<<
  //>>>> Load the new set with groups (mandatory).
  // Attach the same groups from the given set-ID to the new set.
  if($copyGroupsFromID !== -1) {
    $this->logInfo("Cloning group configuration from existing set with ID ($copyGroupsFromID).");
    $model->initFromSkeleton($copyGroupsFromID);
  } else { 		// Just add a default group.
    $this->logInfo("Creating default group [{$this->groupName}] for set.");

    $modelGroup = Mage::getModel('eav/entity_attribute_group');
    $modelGroup->setAttributeGroupName($this->groupName);
    $modelGroup->setAttributeSetId($id);

    // This is optional, and just a sorting index in the case of
    // multiple groups.
    // $modelGroup->setSortOrder(1);

    $model->setGroups(array($modelGroup));
  }

  //<<<<

  // Save the final version of our set.
  try {
    $model->save();
  } catch(Exception $ex) {
    $this->logError("Final attribute-set with name [$setName] could not be saved: " . $ex->getMessage());
    return false;
  }

  if(($groupID = $modelGroup->getId()) == false) {
    $this->logError("Could not get ID from new group [$groupName].");
    return false;
  }

  $this->logInfo("Created attribute-set with ID ($id) and default-group with ID ($groupID).");

  return array(
		'SetID'     => $id,
    'GroupID'   => $groupID,
  );
}

/**
 * Create an attribute.
 *
 * For reference, see Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
 *
 * @return int|false
 */
function createAttribute($labelText, $attributeCode, $values = -1, $productTypes = -1, $setInfo = -1){

  $labelText = trim($labelText);
  $attributeCode = trim($attributeCode);

  if($labelText == '' || $attributeCode == '') {
    $this->logError("Can't import the attribute with an empty label or code.  LABEL= [$labelText]  CODE= [$attributeCode]");
    return false;
  }

  if($values === -1)
    $values = array();

  if($productTypes === -1)
    $productTypes = array();

  if($setInfo !== -1 && (isset($setInfo['SetID']) == false || isset($setInfo['GroupID']) == false)) {
      $this->logError("Please provide both the set-ID and the group-ID of the attribute-set if you'd like to subscribe to one.");
      return false;
  }

  $this->logInfo("Creating attribute [$labelText] with code [$attributeCode].");

  //>>>> Build the data structure that will define the attribute. See
  //     Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().

  $data = array(
	  'is_global'                     => '0',
	  'frontend_input'                => 'text',
	  'default_value_text'            => '',
	  'default_value_yesno'           => '0',
	  'default_value_date'            => '',
	  'default_value_textarea'        => '',
	  'is_unique'                     => '0',
	  'is_required'                   => '0',
	  'frontend_class'                => '',
	  'is_searchable'                 => '1',
	  'is_visible_in_advanced_search' => '1',
	  'is_comparable'                 => '1',
	  'is_used_for_promo_rules'       => '0',
	  'is_html_allowed_on_front'      => '1',
	  'is_visible_on_front'           => '0',
	  'used_in_product_listing'       => '0',
	  'used_for_sort_by'              => '0',
	  'is_configurable'               => '0',
	  'is_filterable'                 => '0',
	  'is_filterable_in_search'       => '0',
	  'backend_type'                  => 'varchar',
	  'default_value'                 => '',
  );

  // Now, overlay the incoming values on to the defaults.
  foreach($values as $key => $newValue)
    if(isset($data[$key]) == false) {
      $this->logError("Attribute feature [$key] is not valid.");
      return false;
    }

    else
      $data[$key] = $newValue;

  // Valid product types: simple, grouped, configurable, virtual, bundle, downloadable, giftcard
  $data['apply_to']       = $productTypes;
  $data['attribute_code'] = $attributeCode;
  $data['frontend_label'] = array(
    0 => $labelText,
    1 => '',
    3 => '',
    2 => '',
    4 => '',
  );

  //<<<<

  //>>>> Build the model.
  $model = Mage::getModel('catalog/resource_eav_attribute');
  $model->addData($data);

  if($setInfo !== -1) {
    $model->setAttributeSetId($setInfo['SetID']);
    $model->setAttributeGroupId($setInfo['GroupID']);
  }

  $entityTypeID = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
  $model->setEntityTypeId($entityTypeID);

  $model->setIsUserDefined(1);

  //<<<<
  // Save.

  try {
      $model->save();
  } catch(Exception $ex) {
    $this->logError("Attribute [$labelText] could not be saved: " . $ex->getMessage());
    return false;
  }

  $id = $model->getId();
  $this->logInfo("Attribute [$labelText] has been saved as ID ($id).");
  return $id;
}
?>