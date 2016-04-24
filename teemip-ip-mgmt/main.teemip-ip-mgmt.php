<?php
// Copyright (C) 2014 TeemIp
//
//   This file is part of TeemIp.
//
//   TeemIp is free software; you can redistribute it and/or modify	
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   TeemIp is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with TeemIp. If not, see <http://www.gnu.org/licenses/>

/**
 * @copyright   Copyright (C) 2014 TeemIp
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/*******************
 * Global constants
 */

define('MAX_NB_OF_IPS_TO_DISPLAY', 4096);

define('MAX_IPV4_VALUE', 4294967295);
define('IPV4_BLOCK_MIN_SIZE', 8);
define('IPV4_SUBNET_MAX_SIZE', 65536);

define('ALL_ORGS', 65536);

define('ACTION_NONE', 0);
define('ACTION_SHRINK', 1);
define('ACTION_SPLIT', 2);
define('ACTION_EXPAND', 3);
define('ACTION_PARENT_BLOCK_IS_DELETED', 4);
define('ACTION_BLOCK_IS_DELETED', 5);

define('GLOBAL_CONFIG_DEFAULT_NAME', 'IP Settings');
define('DEFAULT_BLOCK_LOW_WATER_MARK', 60);
define('DEFAULT_BLOCK_HIGH_WATER_MARK', 80);
define('DEFAULT_SUBNET_LOW_WATER_MARK', 60);
define('DEFAULT_SUBNET_HIGH_WATER_MARK', 80);
define('DEFAULT_IPRANGE_LOW_WATER_MARK', 60);
define('DEFAULT_IPRANGE_HIGH_WATER_MARK', 80);

define('DEFAULT_MAX_FREE_SPACE_OFFERS', 10);
define('DEFAULT_MAX_FREE_IP_OFFERS', 10);
define('DEFAULT_MAX_FREE_IP_OFFERS_WITH_PING', 5);
define('DEFAULT_SUBNET_CREATE_MAX_OFFER', 10);

define('RED', "#cc3300");
define('YELLOW', "#ffff00");
define('GREEN', "#33ff00");

define('TIME_TO_WAIT_FOR_PING_LONG', 3);
define('TIME_TO_WAIT_FOR_PING_SHORT', 1);
define('NUMBER_OF_PINGS', 1);
define('FAIL_KEY_FOR_PING', '100%');

define('NETWORK_IP_CODE', 'Network IP');
define('NETWORK_IP_DESC', 'Subnet IP');
define('GATEWAY_IP_CODE', 'Gateway');
define('GATEWAY_IP_DESC', 'Gateway IP');
define('BROADCAST_IP_CODE', 'Broadcast');
define('BROADCAST_IP_DESC', 'Broadcast IP');

/*********************************************
 * Class for handling IPv4 and IPv6 addresses  
 */

abstract class ormIP
{
	abstract public function IsBiggerOrEqual(ormIP $oIp);
	
	abstract public function IsBiggerStrict(ormIP $oIp);

	abstract public function IsSmallerOrEqual(ormIP $oIp);

	abstract public function IsSmallerStrict(ormIP $oIp);

	abstract public function IsEqual(ormIP $oIp);

	abstract public function BitwiseAnd(ormIP $oIp);

	abstract public function BitwiseOr(ormIP $oIp);
	
	abstract public function BitwiseNot();
	
	abstract public function LeftShift();

	abstract public function IP2dec();

	abstract public function Add(ormIP $oIp);

	abstract public function GetNextIp();

	abstract public function GetPreviousIp();

	abstract public function GetSizeInterval(ormIP $oIp);
}

/**********************
 * Host Name Attribute
 */

class AttributeHostName extends AttributeString
{
	public function GetValidationPattern()
	{
		// By default, pattern matches RFC 1123 plus '_'
		return('^(\d|[a-z]|[A-Z]|_)(\d|[a-z]|[A-Z]|-|_)*$');
	}
}

/************************
 * MAC Address Attribute
 */

class AttributeMacAddress extends AttributeString
{
	public function MakeRealValue($proposedValue, $oHostObj)
	{
		// Translate input value in canonical format used for storage
		// Input value = hyphens (12-34-56-78-90-ab), dots (1234.5678.90ab) or colons (12:34:56:78:90:ab)
		// Canonical Format = colons
		if ($proposedValue != '')
		{
			if ($proposedValue[2] == '-')
			{
				return(strtr($proposedValue, '-', ':'));
			}
			if ($proposedValue[4] == '.')
			{
				$proposedValue = str_replace('.', '', $proposedValue);
				$sOutputMac = '';
				$j = 0;
				for ($i = 0; $i < 12; $i++)
				{
					$sOutputMac[$i + $j] = $proposedValue[$i];
					if (($i > 0) && (is_int(($i - 1)/2)) && ($j < 5))
					{
						$j++;
						$sOutputMac[$i + $j] = ':';
					}
				}
				return(implode('',$sOutputMac));
			}
		}
		return ($proposedValue);
	}
	
	protected function GetMacAtFormat($sMac, $oHostObject)
	{
		// Return $sMac at format set by global parameters
		if (($sMac != '') && ($oHostObject != null))
		{
			$sMacAddressOutputFormat = $oHostObject->GetAttributeParams($this->GetCode());
			switch($sMacAddressOutputFormat)
			{
				case 'hyphens':
				// Return hyphens format
				return(strtr($sMac, ':', '-'));
				
				case 'dots':
				// Return dots format
				$sMac = str_replace(':', '', $sMac);
				$sOutputMac = '';
				$j = 0;
				for ($i = 0; $i < 12; $i++)
				{
					$sOutputMac[$i + $j] = $sMac[$i];
					if (($i == 3) || ($i == 7))
					{
						$j++;
						$sOutputMac[$i + $j] = '.';
					}
				}
				return(implode('',$sOutputMac));
				
				case 'colons':
				default:
				break;
			}
		}
		// Return default = registered = colons format
		return($sMac);
	}
	
	public function GetAsCSV($sValue, $sSeparator = ',', $sTextQualifier = '"', $oHostObject = null, $bLocalize = true)
	{
		$sFrom = array("\r\n", $sTextQualifier);
		$sTo = array("\n", $sTextQualifier.$sTextQualifier);
		$sEscaped = str_replace($sFrom, $sTo, (string)$this->GetMacAtFormat($sValue, $oHostObject));
		return $sTextQualifier.$sEscaped.$sTextQualifier;
	}

	public function GetAsHTML($sValue, $oHostObject = null, $bLocalize = true)
	{
		return Str::pure2html((string)$this->GetMacAtFormat($sValue, $oHostObject));
	}

	public function GetAsXML($sValue, $oHostObject = null, $bLocalize = true)
	{
		// XML being used by programs, we return canonical value of MAC 
		return Str::pure2xml((string)$sValue);
	}

	public function GetEditValue($sAttCode, $oHostObject = null)
	{
		return (string)$this->GetMacAtFormat($sAttCode, $oHostObject);
	}
	
	public function GetValidationPattern()
	{
		// By default, all 3 official pattern (colons, hyphens, dots) are accepted as input
		return('^((\d|([a-f]|[A-F])){2}-){5}(\d|([a-f]|[A-F])){2}$|^((\d|([a-f]|[A-F])){4}.){2}(\d|([a-f]|[A-F])){4}$|^((\d|([a-f]|[A-F])){2}:){5}(\d|([a-f]|[A-F])){2}$');
	}
}

/***********************
 * Percentage Attribute
 */

class AttributeIPPercentage extends AttributeInteger
{
	public function GetAsHTML($sValue, $oHostObject = null, $bLocalize = true)
	{
		// Display attribute as bar graph. Value & colors are provided by object holding attribute. 
		$iWidth = 5; // Total width of the percentage bar graph, in em...
		if ($oHostObject != null)
		{
			$aParams = array();
			$aParams = $oHostObject->GetAttributeParams($this->GetCode());
			$sValue = $aParams ['value'];
			$sColor = $aParams ['color'];
		}
		else
		{
			$sValue = 0;
			$sColor = GREEN;
		}
		$iValue = (int)$sValue;
		$iPercentWidth = ($iWidth * $iValue) / 100;
		return "<div style=\"width:{$iWidth}em;-moz-border-radius: 3px;-webkit-border-radius: 3px;border-radius: 3px;display:inline-block;border: 1px #ccc solid;\"><div style=\"width:{$iPercentWidth}em; display:inline-block;background-color:$sColor;\">&nbsp;</div></div>&nbsp;$sValue %";
	}

	public function GetAsCSV($sValue, $sSeparator = ',', $sTextQualifier = '"', $oHostObject = null, $bLocalize = true)
	{
		if ($oHostObject != null)
		{
			$aParams = array();
			$aParams = $oHostObject->GetAttributeParams($this->GetCode());
			$sValue = $aParams ['value'];
		}
		else
		{
			$sValue = 0;
		}
		//$sEscaped = (string)mylong2ip($sValue);
		$sEscaped = (string)$sValue;
		return $sTextQualifier.$sEscaped.$sTextQualifier;
	}
}

/**************************************
 * Functions to handle masks and sizes
 */

function MaskToSize($Mask)
{
	// Provides size of subnet according to dotted string mask 
	switch ($Mask)
	{
		case "0.0.0.0": return 4294967296;
		case "128.0.0.0": return 2147483648;
		case "192.0.0.0": return 1073741824;
		case "224.0.0.0": return 536870912;
		case "240.0.0.0": return 268435456;
		case "248.0.0.0": return 134217728;
		case "252.0.0.0": return 67108864;
		case "254.0.0.0": return 33554432;
		case "255.0.0.0": return 16777216;
		case "255.128.0.0": return 8388608;
		case "255.192.0.0": return 4194304;
		case "255.224.0.0": return 2097152;
		case "255.240.0.0": return 1048576;
		case "255.248.0.0": return 524288;
		case "255.252.0.0": return 262144;
		case "255.254.0.0": return 131072;
		case "255.255.0.0": return 65536;
		case "255.255.128.0": return 32768;
		case "255.255.192.0": return 16384;
		case "255.255.224.0": return 8192;
		case "255.255.240.0": return 4096;
		case "255.255.248.0": return 2048;
		case "255.255.252.0": return 1024;
		case "255.255.254.0": return 512;
		case "255.255.255.0": return 256;
		case "255.255.255.128": return 128;
		case "255.255.255.192": return 64;
		case "255.255.255.224": return 32;
		case "255.255.255.240": return 16;
		case "255.255.255.248": return 8;
		case "255.255.255.252": return 4;
		case "255.255.255.254": return 2;
		case "255.255.255.255": return 1;
		default: return -1;
	}
}

function BitToMask($iPrefix)
{
	// Provides size of subnet according to dotted string mask 
	switch ($iPrefix)
	{
		case 0: return "0.0.0.0";
		case 1: return "128.0.0.0";
		case 2: return "192.0.0.0"; 
		case 3: return "224.0.0.0"; 
		case 4: return "240.0.0.0"; 
		case 5: return "248.0.0.0"; 
		case 6: return "252.0.0.0"; 
		case 7: return "254.0.0.0"; 
		case 8: return "255.0.0.0"; 
		case 9: return "255.128.0.0"; 
		case 10: return "255.192.0.0"; 
		case 11: return "255.224.0.0"; 
		case 12: return "255.240.0.0"; 
		case 13: return "255.248.0.0"; 
		case 14: return "255.252.0.0"; 
		case 15: return "255.254.0.0"; 
		case 16: return "255.255.0.0"; 
		case 17: return "255.255.128.0"; 
		case 18: return "255.255.192.0"; 
		case 19: return "255.255.224.0"; 
		case 20: return "255.255.240.0";
		case 21: return "255.255.248.0";
		case 22: return "255.255.252.0";
		case 23: return "255.255.254.0";
		case 24: return "255.255.255.0";
		case 25: return "255.255.255.128"; 
		case 26: return "255.255.255.192"; 
		case 27: return "255.255.255.224"; 
		case 28: return "255.255.255.240"; 
		case 29: return "255.255.255.248"; 
		case 30: return "255.255.255.252";
		case 31: return "255.255.255.254";
		case 32: return "255.255.255.255";
		default: return "";
	}
}

function MaskToBit($Mask)
{
	// Provides number of bits within a dotted string mask
	return SizeToBit(MaskToSize($Mask));
}

function SizeToMask ($Size)
{
	// Convert size of subnet into mask
	if (($Size & ($Size - 1)) == 0)
	{
		//return (~($Size - 1));
		return (sprintf("%u", ~($Size - 1)));
	}
	else
	{
		return null;
	}
}

function SizeToBit($Size)
{
	// Provides number of bits for a given subnet size
	$iMask = myip2long("128.0.0.0");
	$i = 1;
	//while(($Size < MaskToSize(mylong2ip($bitMask))) && ($i < 32))
	while(($Size < $iMask) && ($i < 32))
	{
// A revoir pour 64 bits machines
		$iMask = $iMask >> 1;
		$i++;
	}
	if ($i <= 32)  
	{
		return $i;
	}
	else
	{
		return 0;
	}
}

/**************************
 * Functions to handle IPs
 */
 
function myip2long($sIp)
{
	//return(($sIp == '255.255.255.255') ? MAX_IPV4_VALUE : ip2long($sIp)); // Doesn't work for IPs > 128.0.0.0
	return(($sIp == '255.255.255.255') ? MAX_IPV4_VALUE : sprintf("%u", ip2long($sIp))); // OK so far... 
} 

function mylong2ip ($iIp)
{
	return(long2ip($iIp));
}

/******************************************
 * General Configuration related functions
 *  . function GetGlobalIPConfig
 *  . function GetFromGlobalIPConfig
 */

function GetGlobalIPConfig($sOrgId)
{
	// Create Global Config of $sOrgId if it doesn't exist
	// Create basic IP usages at the same time
	$oIpConfig = MetaModel::GetObjectFromOQL("SELECT IPConfig AS conf WHERE conf.org_id = $sOrgId", null, false);
	if ($oIpConfig == null)
	{
		$oIpConfig = MetaModel::NewObject('IPConfig');
		$oIpConfig->Set('org_id', $sOrgId);
		$oIpConfig->DBInsert();
		
		CreateBasicIpUsages($sOrgId);
	}
	return ($oIpConfig);
}

function GetFromGlobalIPConfig($sParameter, $sOrgId)
{
	// Reads $sParameter from Global Config 
	if ($sOrgId != null)
	{
		$oIpConfig = GetGlobalIPConfig($sOrgId);
		return ($oIpConfig->Get($sParameter));
	}
	return null;
}

/*********************************
 * IP Addresses related functions
 *  . CreateBasicIpUsages
 *  . GetIpUsageId
 */

function CreateBasicIpUsages($sOrgId)
{
	GetIpUsageId($sOrgId, NETWORK_IP_CODE);
	GetIpUsageId($sOrgId, GATEWAY_IP_CODE);
	GetIpUsageId($sOrgId, BROADCAST_IP_CODE);
} 

function GetIpUsageId($sOrgId, $sCode)
{
	$oIpUsage = MetaModel::GetObjectFromOQL("SELECT IPUsage AS i WHERE i.name = '$sCode' AND i.org_id = $sOrgId", null, false);
	if ($oIpUsage == null)
	{
		$oIpUsage = MetaModel::NewObject('IPUsage');
		$oIpUsage->Set('org_id', $sOrgId);
		$oIpUsage->Set('name', $sCode);
		switch ($sCode)
		{
			case NETWORK_IP_CODE:
				$sDesc = NETWORK_IP_DESC;
				break;
				
			case GATEWAY_IP_CODE:
				$sDesc = GATEWAY_IP_DESC;
				break;
				
			case BROADCAST_IP_CODE:
				$sDesc = BROADCAST_IP_DESC;
				
			default:
				$sDesc = "";
				break;
		}
		$oIpUsage->Set('description', $sDesc);
		$oIpUsage->DBInsert();
	}
	return ($oIpUsage->GetKey());
}

/******************************
 * Triggers related to IP classes
 *  . IPTriggerOnWaterMark 
 */

class IPTriggerOnWaterMark extends Trigger
{
	public static function Init()
	{
		$aParams = array
		(
			"category" => "bizmodel",
			"key_type" => "autoincrement",
			"name_attcode" => "description",
			"state_attcode" => "",
			"reconc_keys" => array(),
			"db_table" => "priv_trigger_onwatermark",
			"db_key_field" => "id",
			"db_finalclass_field" => "",
			"display_template" => "",
			"icon" => utils::GetAbsoluteUrlModulesRoot().'teemip-ip-mgmt/images/ipbell.png',
		);
		MetaModel::Init_Params($aParams);
		MetaModel::Init_InheritAttributes();
		
		MetaModel::Init_AddAttribute(new AttributeExternalKey("org_id", array("targetclass"=>"Organization", "jointype"=>null, "allowed_values"=>null, "sql"=>"org_id", "is_null_allowed"=>false, "on_target_delete"=>DEL_MANUAL, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeExternalField("org_name", array("allowed_values"=>null, "extkey_attcode"=>'org_id', "target_attcode"=>'name')));
		MetaModel::Init_AddAttribute(new AttributeEnum("target_class", array("allowed_values"=>new ValueSetEnum('IPv4Subnet,IPv4Range,IPv6Subnet,IPv6Range'), "sql"=>"target_class", "default_value"=>"IPv4Subnet", "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("event", array("allowed_values"=>new ValueSetEnum('cross_high,cross_low'), "sql"=>"event", "default_value"=>"cross_high", "is_null_allowed"=>true, "depends_on"=>array())));
		
		// Display lists
		MetaModel::Init_SetZListItems('details', array('org_id', 'description', 'target_class', 'event', 'action_list')); // Attributes to be displayed for the complete details
		MetaModel::Init_SetZListItems('list', array('finalclass', 'target_class', 'description', 'event', 'org_id')); // Attributes to be displayed for a list
	}
	
	public function IsInScope(DBObject $oObject)
	{
		$sTargetClass = $this->Get('target_class');
		return  ($oObject instanceof $sTargetClass);
	}
}

/************************
 * Domain Name Attribute
 */

class AttributeDomainName extends AttributeString
{
	public function GetValidationPattern() 
	{
		// By default, pattern matches RFC 1123 plus '_'
		return('^(\d|[a-z]|[A-Z]|-|_)+((\.(\d|[a-z]|[A-Z]|-|_)+)*)\.?$');
	}
}

/***********************************
 * Plugin to extend the Popup Menus
 */

class IPMgmtExtraMenus implements iPopupMenuExtension
{
	public static function EnumItems($iMenuId, $param)
	{
		switch($iMenuId)
		{
			case iPopupMenuExtension::MENU_OBJLIST_ACTIONS:	// $param is a DBObjectSet
				$oSet = $param;
				if ($oSet->Count() == 1)
				{
					// Menu for single objects only 
					$oObj = $oSet->Fetch();
					
					// Additional actions for IPBlocks
					if ($oObj instanceof IPBlock)
					{
						$oAppContext = new ApplicationContext();
						$sContext = $oAppContext->GetForLink();
						
						// Unique org is selected as we have a single object
						$id = $oObj->GetKey();
						
						$operation = utils::ReadParam('operation', '');
						$sClass = get_class($oObj);
						switch ($operation)
						{
							case 'displaytree':
								if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:Delegate:'.$sClass, Dict::S('UI:IPManagement:Action:Delegate:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=delegate&class=$sClass&id=$id&$sContext"),
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:Shrink:'.$sClass, Dict::S('UI:IPManagement:Action:Shrink:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=shrinkblock&class=$sClass&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Split:'.$sClass, Dict::S('UI:IPManagement:Action:Split:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=splitblock&class=$sClass&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Expand:'.$sClass, Dict::S('UI:IPManagement:Action:Expand:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=expandblock&class=$sClass&id=$id&$sContext"),
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListSpace:'.$sClass, Dict::S('UI:IPManagement:Action:ListSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listspace&class=$sClass&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext"),
									);
								}
								else
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListSpace:'.$sClass, Dict::S('UI:IPManagement:Action:ListSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listspace&class=$sClass&id=$id&$sContext"),
									);
								}
							break;
										
							case 'displaylist':
							default:
								if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:Delegate:'.$sClass, Dict::S('UI:IPManagement:Action:Delegate:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=delegate&class=$sClass&id=$id&$sContext"),
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:Shrink:'.$sClass, Dict::S('UI:IPManagement:Action:Shrink:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=shrinkblock&class=$sClass&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Split:'.$sClass, Dict::S('UI:IPManagement:Action:Split:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=splitblock&class=$sClass&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Expand:'.$sClass, Dict::S('UI:IPManagement:Action:Expand:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=expandblock&class=$sClass&id=$id&$sContext"),
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListSpace:'.$sClass, Dict::S('UI:IPManagement:Action:ListSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listspace&class=$sClass&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext"),
									);
								}
								else
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListSpace:'.$sClass, Dict::S('UI:IPManagement:Action:ListSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listspace&class=$sClass&id=$id&$sContext"),
									);
								}
							break;
						}
					}
					// Additional actions for IPv4Subnets
					elseif ($oObj instanceof IPv4Subnet)
					{
						$oAppContext = new ApplicationContext();
						$sContext = $oAppContext->GetForLink();
						
						// Unique org is selected as we have a single object
						$id = $oObj->GetKey();
						
						$operation = utils::ReadParam('operation', '');
						switch ($operation)
						{
							case 'displaytree':
								if (UserRights::IsActionAllowed('IPv4Subnet', UR_ACTION_MODIFY) == UR_ALLOWED_YES)
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:Shrink:IPv4Subnet', Dict::S('UI:IPManagement:Action:Shrink:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=shrinksubnet&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Split:IPv4Subnet', Dict::S('UI:IPManagement:Action:Split:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=splitsubnet&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Expand:IPv4Subnet', Dict::S('UI:IPManagement:Action:Expand:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=expandsubnet&class=IPv4Subnet&id=$id&$sContext"),
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:ListIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:IPv4Subnet', Dict::S('UI:IPManagement:Action:FindSpace:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=IPv4Subnet&id=$id&$sContext"),
									);
								}
								else
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:ListIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=IPv4Subnet&id=$id&$sContext"),
									);
								}
							break;
										
							case 'displaylist':
							default:
								if (UserRights::IsActionAllowed('IPv4Subnet', UR_ACTION_MODIFY) == UR_ALLOWED_YES)
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:Shrink:IPv4Subnet', Dict::S('UI:IPManagement:Action:Shrink:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=shrinksubnet&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Split:IPv4Subnet', Dict::S('UI:IPManagement:Action:Split:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=splitsubnet&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:Expand:IPv4Subnet', Dict::S('UI:IPManagement:Action:Expand:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=expandsubnet&class=IPv4Subnet&id=$id&$sContext"),
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:ListIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:IPv4Subnet', Dict::S('UI:IPManagement:Action:FindSpace:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=IPv4Subnet&id=$id&$sContext"),
									);
								}
								else
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:ListIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=IPv4Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:IPv4Subnet', Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=IPv4Subnet&id=$id&$sContext"),
									);
								}
							break;
						}
					}
					// Additional actions for IPv6Subnets
					elseif ($oObj instanceof IPv6Subnet)
					{
						$oAppContext = new ApplicationContext();
						$sContext = $oAppContext->GetForLink();
						
						// Unique org is selected as we have a single object
						$id = $oObj->GetKey();
						
						$operation = utils::ReadParam('operation', '');
						switch ($operation)
						{
							case 'displaytree':
							case 'displaylist':
							default:
								if (UserRights::IsActionAllowed('IPv6Subnet', UR_ACTION_MODIFY) == UR_ALLOWED_YES)
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListIps:IPv6Subnet', Dict::S('UI:IPManagement:Action:ListIps:IPv6Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=IPv6Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:IPv6Subnet', Dict::S('UI:IPManagement:Action:FindSpace:IPv6Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=IPv6Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:IPv6Subnet', Dict::S('UI:IPManagement:Action:CsvExportIps:IPv6Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=IPv6Subnet&id=$id&$sContext"),
									);
								}
								else
								{
									$aResult = array(
										new SeparatorPopupMenuItem(),
										new URLPopupMenuItem('UI:IPManagement:Action:ListIps:IPv6Subnet', Dict::S('UI:IPManagement:Action:ListIps:IPv6Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=IPv6Subnet&id=$id&$sContext"),
										new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:IPv6Subnet', Dict::S('UI:IPManagement:Action:CsvExportIps:IPv6Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=IPv6Subnet&id=$id&$sContext"),
									);
								}
							break;
						}
					}
					else
					{
						$aResult = array();
					}
				}
				else
				{
					$aResult = array();
				}
			break;
			
			case iPopupMenuExtension::MENU_OBJLIST_TOOLKIT: // $param is a DBObjectSet
				$oSet = $param;
				$oObj = $oSet->Fetch();
				
				// Additional actions for IPBlocks
				if ($oObj instanceof IPBlock)
				{
					$oAppContext = new ApplicationContext();
					$sContext = $oAppContext->GetForLink();
					
					$operation = utils::ReadParam('operation', '');
					$sClass = get_class($oObj);
					switch ($operation)
					{
						case 'displaytree':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayList:'.$sClass, Dict::S('UI:IPManagement:Action:DisplayList:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaylist&class=$sClass&$sContext"),
							);
						break;
										
						case 'displaylist':
						default:
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayTree:'.$sClass, Dict::S('UI:IPManagement:Action:DisplayTree:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaytree&class=$sClass&$sContext"),
							);
						break;
					}
				}
				// Additional actions for IPSubnets
				else if ($oObj instanceof IPSubnet)
				{
					$oAppContext = new ApplicationContext();
					$sContext = $oAppContext->GetForLink();
					
					$operation = utils::ReadParam('operation', '');
					$sClass = get_class($oObj);
					switch ($operation)
					{
						case 'displaytree':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayList:'.$sClass, Dict::S('UI:IPManagement:Action:DisplayList:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaylist&class=$sClass&$sContext"),
							);
						break;
						
						case 'docalculator':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayList:'.$sClass, Dict::S('UI:IPManagement:Action:DisplayList:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaylist&class=$sClass&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayTree:'.$sClass, Dict::S('UI:IPManagement:Action:DisplayTree:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaytree&class=$sClass&$sContext"),
							);
						break;
										
						case 'displaylist':
						default:
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayTree:'.$sClass, Dict::S('UI:IPManagement:Action:DisplayTree:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaytree&class=$sClass&$sContext"),
							);
						break;
					}
					$aResult[] = new SeparatorPopupMenuItem();
					$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Calculator:'.$sClass, Dict::S('UI:IPManagement:Action:Calculator:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=calculator&class=$sClass&$sContext");
				}
				// Additional actions for Domain
				elseif ($oObj instanceof Domain)
				{
					$oAppContext = new ApplicationContext();
					$sContext = $oAppContext->GetForLink();
					$sFilter = utils::ReadParam('filter', '');
					
					$operation = utils::ReadParam('operation', '');
					switch ($operation)
					{
						case 'displaytree':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								//new URLPopupMenuItem('UI:IPManagement:Action:DisplayList:Domain', Dict::S('UI:IPManagement:Action:DisplayList:Domain'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaylist&class=Domain&$sContext&filter=$sFilter"),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayList:Domain', Dict::S('UI:IPManagement:Action:DisplayList:Domain'), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=search&$sContext&filter=$sFilter"),
							);
						break;
										
						case 'displaylist':
						default:
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:DisplayTree:Domain', Dict::S('UI:IPManagement:Action:DisplayTree:Domain'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=displaytree&class=Domain&$sContext&filter=$sFilter"),
							);
						break;
					}
				}
				else
				{
					$aResult = array();
				}
			break;
			
			case iPopupMenuExtension::MENU_OBJDETAILS_ACTIONS: // $param is a DBObject
				$oObj = $param;
				
				// Additional actions for IPBlocks
				if ($oObj instanceof IPBlock)
				{
					$oAppContext = new ApplicationContext();
					$sContext = $oAppContext->GetForLink();
					$id = $oObj->GetKey();
					
					$sClass = get_class($oObj);
					if (UserRights::IsActionAllowed('IPBlock', UR_ACTION_MODIFY) == UR_ALLOWED_YES)
					{
						$aResult = array(
							new SeparatorPopupMenuItem(),
							new URLPopupMenuItem('UI:IPManagement:Action:Delegate:'.$sClass, Dict::S('UI:IPManagement:Action:Delegate:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=delegate&class=$sClass&id=$id&$sContext"),
							new SeparatorPopupMenuItem(),
							new URLPopupMenuItem('UI:IPManagement:Action:Shrink:'.$sClass, Dict::S('UI:IPManagement:Action:Shrink:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=shrinkblock&class=$sClass&id=$id&$sContext"),
							new URLPopupMenuItem('UI:IPManagement:Action:Split:'.$sClass, Dict::S('UI:IPManagement:Action:Split:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=splitblock&class=$sClass&id=$id&$sContext"),
							new URLPopupMenuItem('UI:IPManagement:Action:Expand:'.$sClass, Dict::S('UI:IPManagement:Action:Expand:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=expandblock&class=$sClass&id=$id&$sContext"),
							new SeparatorPopupMenuItem(),
						);
					}
					else
					{
						$aResult = array();
					}
					$operation = utils::ReadParam('operation', '');
					switch ($operation)
					{
						case 'apply_new':
						case 'apply_modify':
						case 'details':
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:ListSpace:'.$sClass, Dict::S('UI:IPManagement:Action:ListSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listspace&class=$sClass&id=$id&$sContext");
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
						break;
						
						case 'listspace':
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext");
						break;
						
						case 'dofindspace':
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:ListSpace:'.$sClass, Dict::S('UI:IPManagement:Action:ListSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listspace&class=$sClass&id=$id&$sContext");
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext");
						break;
						
						default:
						break;
					}
				}
				// Additional actions for IPSubnets
				else if ($oObj instanceof IPSubnet)
				{
					$oAppContext = new ApplicationContext();
					$sContext = $oAppContext->GetForLink();
					$id = $oObj->GetKey();

					$aResult = array();
					$sClass = get_class($oObj);
					if ($oObj instanceof IPv4Subnet)
					{
						if (UserRights::IsActionAllowed('IPv4Subnet', UR_ACTION_MODIFY) == UR_ALLOWED_YES)
						{
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:Shrink:IPv4Subnet', Dict::S('UI:IPManagement:Action:Shrink:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=shrinksubnet&class=IPv4Subnet&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:Split:IPv4Subnet', Dict::S('UI:IPManagement:Action:Split:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=splitsubnet&class=IPv4Subnet&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:Expand:IPv4Subnet', Dict::S('UI:IPManagement:Action:Expand:IPv4Subnet'), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=expandsubnet&class=IPv4Subnet&id=$id&$sContext"),
								new SeparatorPopupMenuItem(),
							);
						}
					}
					else if ($oObj instanceof IPv6Subnet)
					{
						if (UserRights::IsActionAllowed('IPv4Subnet', UR_ACTION_MODIFY) == UR_ALLOWED_YES)
						{
							$aResult = array(
								new SeparatorPopupMenuItem(),
							);
						}
					}
					else
					{
						return array();
					}
					
					$operation = utils::ReadParam('operation', '');
					switch ($operation)
					{
						case 'apply_new':
						case 'apply_modify':
						case 'details':
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext");
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext");
						break;
						
						case 'listips':
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext");
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext");
						break;
						
						case 'dofindspace':
						case 'docalculator':
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext");
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext");
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext");
						break;
						
						case 'csvexportips':
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext");
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext");
						break;
						
						case 'dolistips':
						case 'docsvexportips':
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext");
							if (UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY) == UR_ALLOWED_YES)
							{
								$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:FindSpace:'.$sClass, Dict::S('UI:IPManagement:Action:FindSpace:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=findspace&class=$sClass&id=$id&$sContext");
							}
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext");
							$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext");
						break;
						
						default:
						break;
					}
					$aResult[] = new SeparatorPopupMenuItem();
					$aResult[] = new URLPopupMenuItem('UI:IPManagement:Action:Calculator:'.$sClass, Dict::S('UI:IPManagement:Action:Calculator:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=calculator&class=$sClass&id=$id&$sContext");
				}
				// Additional actions for IPRange
				else if ($oObj instanceof IPRange)
				{
					$oAppContext = new ApplicationContext();
					$sContext = $oAppContext->GetForLink();
					$id = $oObj->GetKey();
					
					$operation = utils::ReadParam('operation', '');
					$sClass = get_class($oObj);
					switch ($operation)
					{
						case 'listips':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext"),
							);
							break;
							
						case 'csvexportips':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext"),
							);
							break;
							
						case 'dolistips':
						case 'docsvexportips':
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:Details:'.$sClass, Dict::S('UI:IPManagement:Action:Details:'.$sClass), utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=details&class=$sClass&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext"),
							);
							break;
							
						default:
							$aResult = array(
								new SeparatorPopupMenuItem(),
								new URLPopupMenuItem('UI:IPManagement:Action:ListIps:'.$sClass, Dict::S('UI:IPManagement:Action:ListIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=listips&class=$sClass&id=$id&$sContext"),
								new URLPopupMenuItem('UI:IPManagement:Action:CsvExportIps:'.$sClass, Dict::S('UI:IPManagement:Action:CsvExportIps:'.$sClass), utils::GetAbsoluteUrlModulesRoot()."teemip-ip-mgmt/ui.teemip-ip-mgmt.php?operation=csvexportips&class=$sClass&id=$id&$sContext"),
							);
							break;
					}
				}
				else
				{
					$aResult = array();
				}
			break;
			
			case iPopupMenuExtension::MENU_DASHBOARD_ACTIONS:
				// $param is a Dashboard
				$aResult = array();
				break;
			
			default:
				// Unknown type of menu, do nothing
				$aResult = array();
				break;
		}
		return $aResult;
	}
}
