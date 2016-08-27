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

class _IPv4Range extends IPRange
{
	/**
	 * Returns icon to be displayed
	 */
	public function GetIcon($bImgTag = true, $bXsIcon = false)
	{ 
		if ($bXsIcon)
		{
			$sIcon = utils::GetAbsoluteUrlModulesRoot().'teemip-ip-mgmt/images/iprange-xs.png';
		}
		else
		{
			$sIcon = utils::GetAbsoluteUrlModulesRoot().'teemip-ip-mgmt/images/iprange.png';
		}
		return ("<img src=\"$sIcon\" style=\"vertical-align:middle;\"/>");
	}

	/**
	 * Returns size of range
	 */
	public function GetSize()
	{
		$sFirstIp = $this->Get('firstip');
		$sLastIp = $this->Get('lastip');
		$iSize = myip2long ($sLastIp) - myip2long($sFirstIp) + 1;
		return $iSize;
	}

	/**
	 * Compute % of IP addresses registered in data base in IP range
	 */
	public function GetOccupancy()
	{
		$sOrgId = $this->Get('org_id');
		$sFirstIp = $this->Get('firstip');
		$sLastIp = $this->Get('lastip');

		$iSize = myip2long ($sLastIp) - myip2long($sFirstIp) + 1;
		$oIpRegisteredSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Address AS i WHERE INET_ATON('$sFirstIp') <= INET_ATON(i.ip) AND INET_ATON(i.ip) <= INET_ATON('$sLastIp') AND i.org_id = $sOrgId"));
		return ($oIpRegisteredSet->Count() / $iSize) * 100;
	}
	
	/**
	 * List IP addresses in IP range in CSV format
	 */
	public function GetIPsAsCSV($aParam)
	{
		// Define first and last IPs to display
		$sFirstIp = $aParam['first_ip'];
		$sRangeFirstIp = $this->Get('firstip');
		if ($sFirstIp == '')
		{
			$sFirstIp = $sRangeFirstIp;
		}
		$sLastIp = $aParam['last_ip'];
		$sRangeLastIp = $this->Get('lastip');
		if ($sLastIp == '')
		{
			$sLastIp = $sRangeLastIp;
		}

		// Get list of registered IPs in range
		$sOrgId = $this->Get('org_id');
		$iFirstIp = myip2long($sFirstIp);
		$iLastIp = myip2long($sLastIp);
		$oIpRegisteredSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Address AS i WHERE INET_ATON('$sFirstIp') <= INET_ATON(i.ip)  AND INET_ATON(i.ip) <= INET_ATON('$sLastIp')  AND i.org_id = $sOrgId"));
						
		// List exported parameters
		$sHtml = "Registered,Id";
		$aParam = array('org_name', 'ip', 'status', 'fqdn', 'usage_name', 'comment', 'requestor_name', 'release_date');
		foreach($aParam as $sAttCode)
		{
				$sHtml .= ','.MetaModel::GetLabel('IPv4Address', $sAttCode);
		}
		$sHtml .= "\n";
						
		// List all IPs of range now in IP order 
		$aIpRegistered = $oIpRegisteredSet->GetColumnAsArray('ip', false);
		$iAnIp = $iFirstIp;
		while ($iAnIp <= $iLastIp)
		{
			$sAnIp = mylong2ip($iAnIp);
			if (!in_array($sAnIp, $aIpRegistered))
			{
				$sHtml .= "no,,,".$sAnIp.",free,,,,,,,\n";
			}
			else
			{
				$oIpRegisteredSet->Rewind();
				$oIpRegistered = $oIpRegisteredSet->Fetch();  
				while ($sAnIp != $oIpRegistered->Get('ip'))
				{
					$oIpRegistered = $oIpRegisteredSet->Fetch();
				}
				$sHtml .= "yes,".$oIpRegistered->GetKey().",";
				$sHtml .= $oIpRegistered->Get('org_id').",";
				$sHtml .= $oIpRegistered->Get('ip').",";
				$sHtml .= $oIpRegistered->Get('status').",";
				$sHtml .= $oIpRegistered->Get('fqdn').",";
				$sHtml .= $oIpRegistered->Get('usage_name').",";
				$sHtml .= $oIpRegistered->Get('comment').",";
				$sHtml .= $oIpRegistered->Get('requestor_name').",";
				$sHtml .= $oIpRegistered->Get('release_date')."\n";
			}
			$iAnIp++;
		}
		return ($sHtml);
	}
	
	/**
	 * Check if IP is in range
	 */
	function DoCheckIpInRange($sIp)
	{
		$iIp = myip2long($sIp);
		$iFirstIp = myip2long($this->Get('firstip'));
		$iLastIp = myip2long($this->Get('lastip'));
		if (($iFirstIp <= $iIp) && ($iIp <= $iLastIp))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Check if IPs can be listed
	 */
	function DoCheckToListIps($aParam)
	{
		$sRangeFirstIp = $this->Get('firstip');
		$iRangeFirstIp = myip2long($sRangeFirstIp);
		$sRangeLastIp = $this->Get('lastip');
		$iRangeLastIp = myip2long($sRangeLastIp);

		$sFirstIp = $aParam['first_ip'];
		if ($sFirstIp != '')
		{
			$iFirstIp = myip2long($sFirstIp);
			if (($iFirstIp < $iRangeFirstIp) || ($iRangeLastIp < $iFirstIp))
			{
				return (Dict::Format('UI:IPManagement:Action:DoListIps:IPv4Range:FirstIPOutOfRange'));
			}
		}
		
		$sLastIp = $aParam['last_ip'];
		if ($sLastIp != '')
		{
			$iLastIp = myip2long($sLastIp);
			if (($iLastIp < $iRangeFirstIp) || ($iRangeLastIp < $iLastIp))
			{
				return (Dict::Format('UI:IPManagement:Action:DoListIps:IPv4Range:LastIPOutOfRange'));
			}
		}
		
		if (($sFirstIp != '') && ($sLastIp != ''))
		{
			if ($iFirstIp > $iLastIp)
			{
				return (Dict::Format('UI:IPManagement:Action:DoListIps:IPv4Range:FirstIpBiggerThanLastIp'));
			}
		}
		return '';
	}
	
	/**
	 * Displays list of IP addresses within GUI
	 */
	function DoListIps(WebPage $oP, $iChangeId, $aParam)
	{
		// Define first and last IPs to display
		$sFirstIp = $aParam['first_ip'];
		$sRangeFirstIp = $this->Get('firstip');
		if ($sFirstIp == '')
		{
			$sFirstIp = $sRangeFirstIp;
		}
		$bPrintDummyFirstLine = ($sFirstIp != $sRangeFirstIp) ? true : false;
		$sLastIp = $aParam['last_ip'];
		$sRangeLastIp = $this->Get('lastip');
		if ($sLastIp == '')
		{
			$sLastIp = $sRangeLastIp;
		}
		$bPrintDummyLastLine = ($sLastIp != $sRangeLastIp) ? true : false;

		// Get list of registered IPs in range
		$iId = $this->GetKey();
		$sOrgId = $this->Get('org_id');
		$iFirstIp = myip2long($sFirstIp);
		$iLastIp = myip2long($sLastIp);
		$oIpRegisteredSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Address AS ipv4 WHERE INET_ATON('$sFirstIp') <= INET_ATON(ipv4.ip) AND INET_ATON(ipv4.ip) <= INET_ATON('$sLastIp') AND ipv4.org_id = $sOrgId"));
		$aRegisteredIPs = $oIpRegisteredSet->GetColumnAsArray('ip', false);
			
		// Preset display of name and subnet attributes
		$sHtml = "&nbsp;[".$this->GetAsHTML('firstip')." - ".$this->GetAsHTML('lastip')."]";

		$iSubnetId = $this->Get('subnet_id');
		$sStatusIp = $aParam['status_ip'];
		$sShortName = $aParam['short_name'];
		$iDomainId = $aParam['domain_id'];
		$iUsageId = $aParam['usage_id'];
		$iRequestorId = $aParam['requestor_id'];
		
		$iAnIp = $iFirstIp;
		$iVIdCounter = 1;
			
		// Check user rights
		$UserHasRightsToCreate = (UserRights::IsActionAllowed('IPv4Address', UR_ACTION_MODIFY) == UR_ALLOWED_YES) ? true : false;
	
		// Display first IP
		$oP->add("<ul>\n");
		$oP->add("<li>".$this->GetHyperlink().$sHtml."<ul>\n");
	
		// ... and dummy line if display doesn't start at first IP
		if ($bPrintDummyFirstLine)
		{
			$oP->add("<li>&nbsp;&nbsp;...&nbsp;//&nbsp;... </li>");
		}
		
		// Display other IPs as list
		while ($iAnIp <= $iLastIp)
		{
			$sAnIp = mylong2ip($iAnIp);
			if (in_array($sAnIp, $aRegisteredIPs))
			{
				// Found registered IP
				$oIpRegisteredSet->Rewind();
				$oIpRegistered = $oIpRegisteredSet->Fetch();
				while ($oIpRegistered->Get('ip') != $sAnIp)
				{
					$oIpRegistered = $oIpRegisteredSet->Fetch();
				}
				$oP->add("<li>".$oIpRegistered->GetHyperlink()."&nbsp;&nbsp; - ".$oIpRegistered->GetAsHTML('status')."&nbsp;&nbsp; - ".$oIpRegistered->Get('fqdn'));
			}
			else
			{
				// If user has rights to create IPs
				// Display unregistered IP with icon to create it
				if  ($UserHasRightsToCreate)
				{
					$iVId = $iVIdCounter++;
					$sHTMLValue = "<li><div><span id=\"v_{$iVId}\">";
					$sHTMLValue .= "<img style=\"border:0;vertical-align:middle;cursor:pointer;\" src=\"".utils::GetAbsoluteUrlModulesRoot()."/teemip-ip-mgmt/images/ipmini-add-xs.png\" onClick=\"oIpWidget_{$iVId}.DisplayCreationForm();\"/>&nbsp;";
					$sHTMLValue .= "&nbsp;".$sAnIp."&nbsp;&nbsp;";
					$sHTMLValue .= "</span></div>";
					$oP->add($sHTMLValue);	
					$oP->add_ready_script(
<<<EOF
					oIpWidget_{$iVId} = new IpWidget($iVId, 'IPv4Address', $iChangeId, {'org_id': '$sOrgId', 'subnet_id': '$iSubnetId', 'range_id': '$iId', 'ip': '$sAnIp', 'status': '$sStatusIp', 'short_name': '$sShortName', 'domain_id': '$iDomainId', 'usage_id': '$iUsageId', 'requestor_id': '$iRequestorId'});
EOF
					);
				}
				else
				{
					$oP->add("<li>".$sAnIp);
				}
			}
			$oP->add("</li>\n");
			$iAnIp++;
		}
		
		// Add dummy line if display doesn't finish at broadcast IP
		if ($bPrintDummyLastLine)
		{
			$oP->add("<li>&nbsp;&nbsp;...&nbsp;//&nbsp;... </li>");
		}
		$oP->add("</ul></li></ul>\n");
	}
	
	/**
	 * Check if IPs can be listed
	 */
	function DoCheckToCsvExportIps($aParam)
	{
		$sRangeFirstIp = $this->Get('firstip');
		$iRangeFirstIp = myip2long($sRangeFirstIp);
		$sRangeLastIp = $this->Get('lastip');
		$iRangeLastIp = myip2long($sRangeLastIp);

		$sFirstIp = $aParam['first_ip'];
		if ($sFirstIp != '')
		{
			$iFirstIp = myip2long($sFirstIp);
			if (($iFirstIp < $iRangeFirstIp) || ($iRangeLastIp < $iFirstIp))
			{
				return (Dict::Format('UI:IPManagement:Action:DoCsvExportIps:IPv4Range:FirstIPOutOfRange'));
			}
		}
		
		$sLastIp = $aParam['last_ip'];
		if ($sLastIp != '')
		{
			$iLastIp = myip2long($sLastIp);
			if (($iLastIp < $iRangeFirstIp) || ($iRangeLastIp < $iLastIp))
			{
				return (Dict::Format('UI:IPManagement:Action:DoCsvExportIps:IPv4Range:LastIPOutOfRange'));
			}
		}
		
		if (($sFirstIp != '') && ($sLastIp != ''))
		{
			if ($iFirstIp > $iLastIp)
			{
				return (Dict::Format('UI:IPManagement:Action:DoCsvExportIps:IPv4Range:FirstIpBiggerThanLastIp'));
			}
		}
		return '';
	}
	
	/**
	 * Display attributes associated operation
	 */
	function DisplayActionFieldsForOperation(WebPage $oP, $sOperation, $iFormId, $aDefault)
	{
		$oP->add("<table>");
		$oP->add('<tr><td style="vertical-align:top">');
		
		switch ($sOperation)
		{
			case 'listips':
			case 'csvexportips':
				if ($sOperation == 'listips')
				{
					$sLabelOfAction1 = Dict::S('UI:IPManagement:Action:ListIps:IPv4Range:FirstIP');
					$sLabelOfAction2 = Dict::S('UI:IPManagement:Action:ListIps:IPv4Range:LastIP');
					
					// Sub title
					$oP->add("<b>".Dict::S('UI:IPManagement:Action:ListIps:IPv4Range:Subtitle_ListRange')."</b>\n");
				}
				else
				{
					$sLabelOfAction1 = Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Range:FirstIP');
					$sLabelOfAction2 = Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Range:LastIP');
					
					// Sub title
					$oP->add("<b>".Dict::S('UI:IPManagement:Action:CsvExportIps:IPv4Range:Subtitle_ListRange')."</b>\n");
				}
				
				// New first IP
				$sAttCode = 'firstip';
				$sInputId = $iFormId.'_'.'firstip';
				$oAttDef = MetaModel::GetAttributeDef('IPv4Range', 'firstip');
				$sDefault = (array_key_exists('firstip', $aDefault)) ? $aDefault['firstip'] : '';
				$sHTMLValue = cmdbAbstractObject::GetFormElementForField($oP, 'IPv4Range', $sAttCode, $oAttDef, $sDefault, '', $sInputId, '', '', '');
				$aDetails[] = array('label' => '<span title="">'.$sLabelOfAction1.'</span>', 'value' => $sHTMLValue);
				
				// New last IP
				$sAttCode = 'lastip';
				$sInputId = $iFormId.'_'.'lastip';
				$oAttDef = MetaModel::GetAttributeDef('IPv4Range', 'lastip');
				$sDefault = (array_key_exists('lastip', $aDefault)) ? $aDefault['lastip'] : '';
				$sHTMLValue = cmdbAbstractObject::GetFormElementForField($oP, 'IPv4Range', $sAttCode, $oAttDef, $sDefault, '', $sInputId, '', '', '');
				$aDetails[] = array('label' => '<span title="">'.$sLabelOfAction2.'</span>', 'value' => $sHTMLValue);
				
				$oP->Details($aDetails);
				$oP->add('</td></tr>');
				
				// Cancell button
				$iObjId = $this->GetKey();
				$oP->add("<tr><td><button type=\"button\" class=\"action\" onClick=\"BackToDetails('IPv4Range', $iObjId)\"><span>".Dict::S('UI:Button:Cancel')."</span></button>&nbsp;&nbsp;");
			break;
			
			default:
			break;
		};
				
		// Apply button
		$oP->add("&nbsp;&nbsp<button type=\"submit\" class=\"action\"><span>".Dict::S('UI:Button:Apply')."</span></button></td></tr>");
		
		$oP->add("</table>");
	}

	/**
	 * Displays the tabs related to IPv4Ranges
	 */
	function DisplayBareRelations(WebPage $oPage, $bEditMode = false)
	{
		// Execute parent function first 
		parent::DisplayBareRelations($oPage, $bEditMode);
		
		if (!$this->IsNew())
		{
			$sOrgId = $this->Get('org_id');
			$sFirstIp = $this->Get('firstip');
			$sLastIp = $this->Get('lastip');
			$iFirstIp = myip2long($sFirstIp);
			$iLastIp = myip2long ($sLastIp);
			
			$iSize = $iLastIp - $iFirstIp + 1;
			
			$aExtraParams = array();
			$aExtraParams['menu'] = false;			
			
			// Tab for Registered IPs
			$oIpAssignedSearch = DBObjectSearch::FromOQL("SELECT IPv4Address AS i WHERE INET_ATON(i.ip) >= INET_ATON('$sFirstIp') AND INET_ATON(i.ip) <= INET_ATON('$sLastIp')	AND i.org_id = $sOrgId");
			$oIpAssignedSet = new CMDBObjectSet($oIpAssignedSearch);
			$iCountAssigned = $oIpAssignedSet->Count();
			$iCountAllocated = 0;
			while ($oIpAssigned = $oIpAssignedSet->Fetch())
			{
				if ($oIpAssigned->Get('status') == 'allocated')
				{
					$iCountAllocated++;
				}
			}
			$iCountReserved = $iCountAssigned - $iCountAllocated;
			$oPage->SetCurrentTab(Dict::Format('Class:IPRange/Tab:ipregistered', $iCountAssigned));
			$oPage->p(MetaModel::GetClassIcon('IPv4Address').'&nbsp;'.Dict::Format('Class:IPRange/Tab:ipregistered+'));
			$oPage->p($this->GetAsHTML('occupancy').Dict::Format('Class:IPRange/Tab:ipregistered-count', $iCountReserved, $iCountAllocated, $iSize));
			$oBlock = new DisplayBlock($oIpAssignedSearch, 'list');
			$oBlock->Display($oPage, 'ip_addresses', $aExtraParams);
		}
	}
	
	/**
	 * Check validity of new IP attributes before creation
	 */
	function DoCheckToWrite()
	{
		// Run standard iTop checks first
		parent::DoCheckToWrite();
		
		$sOrgId = $this->Get('org_id');
		if ($this->IsNew())
		{
			$iKey = -1;
		}
		else
		{
			$iKey = $this->GetKey();
		}
		$sRange = $this->Get('range');
		$iFirstIp = myip2long($this->Get('firstip'));
		$iLastIp = myip2long($this->Get('lastip'));
		$iSubnetId = $this->Get('subnet_id');	 
		
		// If check is done during subnet expand, skip checks
		if ($this->Get('write_reason') == 'expand')
		{
			// Reset reason for action
			$this->Set('write_reason', 'none');
		}
		else
		{
			// Check that 1st Ip is smaller than last one
			if ($iFirstIp >= $iLastIp)
			{
				$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:Reverted');
				return;
			}
			
			// Make sure range is fully contained in subnet
			$oSubnet = MetaModel::GetObject('IPv4Subnet', $this->Get('subnet_id'), true /* MustBeFound */);
			$iSubnetBroadcastIp = myip2long($oSubnet->Get('broadcastip'));
			if (($iFirstIp < myip2long($oSubnet->Get('ip'))) || ($iSubnetBroadcastIp < $iLastIp))
			{
				$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:NotInSubnet');
				return;
			}
			
			// Make sure range doesn't collide with another range within subnet
			$oRangeSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Range AS r WHERE r.subnet_id = '$iSubnetId' AND r.org_id = $sOrgId AND r.id != $iKey"));
			while ($oRange = $oRangeSet->Fetch())
			{
				$iCurrentFirstIp = myip2long($oRange->Get('firstip'));
				$iCurrentLastIp = myip2long($oRange->Get('lastip'));
				
				// Check that name doesn't already exist in same subnet
				if ($oRange->Get('range') == $sRange)
				{
					$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:NameExist');
					return;
				}
				// Does the range already exist?
				if (($iCurrentFirstIp == $iFirstIp) && ($iCurrentLastIp == $iLastIp))
				{
					$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:Collision0');
					return;
				}
				// Is new first Ip part of an existing range?
				if (($iCurrentFirstIp <= $iFirstIp) && ($iFirstIp <= $iCurrentLastIp))
				{
					$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:Collision1');
					return;
				}
				// Is new last Ip part of an existing range?
				if (($iCurrentFirstIp <= $iLastIp) && ($iLastIp <= $iCurrentLastIp))
				{
					$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:Collision2');
					return;
				}
				// Is new range including an existing one?
 				if (($iFirstIp < $iCurrentFirstIp) && ($iCurrentLastIp < $iLastIp))
				{
					$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPRange:Collision3');
					return;
				}
			}
		}
	}
	
	/**
	 * Perform specific tasks related to IPv4 range creation:
	 */	 
	protected function AfterInsert()
	{
		parent::AfterInsert();
		
		$iOrgId = $this->Get('org_id');
		$iId = $this->GetKey();
		$sFirstIp = $this->Get('firstip');
		$sLastIp = $this->Get('lastip');
				
		// Make sure all IPs belonging to range are attached to it
		$oIpRegisteredSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Address AS i WHERE INET_ATON('$sFirstIp') <= INET_ATON(i.ip) AND INET_ATON(i.ip) <= INET_ATON('$sLastIp') AND i.org_id = $iOrgId"));
		while ($oIpRegistered = $oIpRegisteredSet->Fetch())
		{
			if ($oIpRegistered->Get('range_id') != $iId)
			{
				$oIpRegistered->Set('range_id', $iId);
				$oIpRegistered->DBUpdate();	
			}
		}
	}
	
	/**
	 * Perform specific tasks related to IPv4 range update:
	 */	 
	protected function AfterUpdate()
	{
		parent::AfterUpdate();
		
		$iOrgId = $this->Get('org_id');
		$iId = $this->GetKey();
		$sFirstIp = $this->Get('firstip');
		$sLastIp = $this->Get('lastip');
				
		// Make sure all IPs belonging to range are attached to it
		$oIpRegisteredSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Address AS i WHERE INET_ATON('$sFirstIp') <= INET_ATON(i.ip) AND INET_ATON(i.ip) <= INET_ATON('$sLastIp') AND i.org_id = $iOrgId"));
		while ($oIpRegistered = $oIpRegisteredSet->Fetch())
		{
			if ($oIpRegistered->Get('range_id') != $iId)
			{
				$oIpRegistered->Set('range_id', $iId);
				$oIpRegistered->DBUpdate();	
			}
		}

		// Make sure all IPs ouside of range are NOT attached to it
		$oIpRegisteredSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv4Address AS i WHERE i.range_id = $iId AND (INET_ATON(i.ip) < INET_ATON('$sFirstIp') OR INET_ATON('$sLastIp') < INET_ATON(i.ip))"));
		while ($oIpRegistered = $oIpRegisteredSet->Fetch())
		{
			$oIpRegistered->Set('range_id', 0);
			$oIpRegistered->DBUpdate();
		}		
	}
	
	/**
	 * Change flag of attributes that shouldn't be modified beside creation.
	 */
	public function GetAttributeFlags($sAttCode, &$aReasons = array(), $sTargetState = '')
	{
		if ((!$this->IsNew()) && ($sAttCode == 'subnet_id'))
		{
			return OPT_ATT_READONLY;
		}
		return parent::GetAttributeFlags($sAttCode, $aReasons, $sTargetState);
	}		
}
