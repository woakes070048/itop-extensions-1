<?php
SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'teemip-ip-mgmt/2.0.0',
	array(
		// Identification
		//
		'label' => 'IP Management',
		'category' => 'business',
		
		// Setup
		//
		'dependencies' => array(
			'itop-config-mgmt/2.0.0',
			'teemip-network-mgmt/2.0.0'
		),
		'mandatory' => false,
		'visible' => true,
		
		// Components
		//
		'datamodel' => array(
			'model.teemip-ip-mgmt.php',
			'main.teemip-ip-mgmt.php',
		),
		'data.struct' => array(
			//'data.struct.IPAudit.xml',
		),
		'data.sample' => array(
			'data.sample.IPGlue.xml',
			'data.sample.IPv4Block.xml',
			'data.sample.lnkIPv4BlockToLocation.xml',
			'data.sample.IPv4Subnet.xml',
			'data.sample.lnkIPv4SubnetToLocation.xml',
			'data.sample.IPRangeUsage.xml',
			'data.sample.IPv4Range.xml',
			'data.sample.IPUsage.xml',
			'data.sample.IPv4Address.xml',
		),
		
		// Documentation
		//
		'doc.manual_setup' => '',
		'doc.more_information' => '',
		
		// Default settings
		//
		'settings' => array(
		),
	)
);
