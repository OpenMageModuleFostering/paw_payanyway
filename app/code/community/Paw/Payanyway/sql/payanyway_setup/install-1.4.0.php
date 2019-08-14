<?php

/** @var $this Mage_Core_Model_Resource_Setup */
$installer = $this;

$connection = $installer->getConnection();
$installer->startSetup();

$data = array(
	array('pending_payanyway', 'Pending PayAnyWay')
);

// Insert statuses
$connection = $installer->getConnection()->insertArray(
	$installer->getTable('sales/order_status'),
	array('status', 'label'),
	$data
);
$installer->endSetup();