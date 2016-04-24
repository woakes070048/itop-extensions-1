<?php
// Copyright (C) 2010 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-sla-computation/2.0.1',
	array(
		// Identification
		//
		'label' => 'Enhanced SLA Computation',
		'category' => 'sla',

		// Setup
		//
		'dependencies' => array(
			'itop-sla-computation/1.0.0',
			'itop-service-mgmt/2.0.0||itop-service-mgmt-provider/2.0.0', // Needed to place new menu entries
		),
		'mandatory' => true,
		'visible' => false,

		// Components
		//
		'datamodel' => array(
			'model.combodo-sla-computation.php',
			'main.combodo-sla-computation.php'
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			'coverage_oql' => 'SELECT CoverageWindow', 	// How to retrive the Coverage object for a given ticket (:this)
			'holidays_oql' => 'SELECT Holiday', 	// How to retrive the list of Holidays for a given ticket (:this)
		),
	)
);


?>
