<?php
SetupWebPage::AddModule(
	__FILE__,
	'teemip-config-mgmt-adaptor/2.0.0',
	array(
		// Identification
		//
		'label' => 'iTop Config Mgmt Adaptor',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(
			'itop-config-mgmt/2.0.0',
			'teemip-ip-mgmt/2.0.0'
		),
		'mandatory' => false,
		'visible' => true, // To prevent auto-install but shall not be listed in the install wizard
 		'auto_select' => 'SetupInfo::ModuleIsSelected("teemip-ip-mgmt") && SetupInfo::ModuleIsSelected("itop-config-mgmt")',

		// Components
		//
		'datamodel' => array(
		),
		'data.struct' => array(
		),
		'data.sample' => array(
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
