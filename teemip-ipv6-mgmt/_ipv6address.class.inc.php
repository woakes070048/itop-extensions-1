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

class _IPv6Address extends IPAddress
{
	function GetName()
	{
		return $this->GetAsHtml('ip');
	}
	
	/**
	 * Get the subnet mask of the subnet that the IP belongs to, if any.
	 */
	function GetSubnetMaskFromIp()
	{
		$sIp = $this->Get('ip')->ToString();
		$sOrgId = $this->Get('org_id');
		$oSubnetSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv6Subnet AS s WHERE s.ip <= :ip AND :ip <= s.lastip AND s.org_id = $sOrgId",  array('ip' => $sIp)));
		if ($oSubnetSet->Count() != 0)
		{ 
			$oSubnet = $oSubnetSet->Fetch();
			return ($oSubnet->Get('mask'));
		}
		return "";
	}
	
	/**
	 * Get the gateway of the subnet that the IP belongs to, if any.
	 */
	function GetSubnetGatewayFromIp()
	{
		$sIp = $this->Get('ip')->ToString();
		$sOrgId = $this->Get('org_id');
		$oSubnetSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv6Subnet AS s WHERE s.ip <= :ip AND :ip <= s.lastip AND s.org_id = $sOrgId",  array('ip' => $sIp)));
		if ($oSubnetSet->Count() != 0)
		{ 
			$oSubnet = $oSubnetSet->Fetch();
			$oGatewayIp = $oSubnet->Get('gatewayip');
			return ($oGatewayIp);
		}
		return "";
	}
	
	/*
	 * Check if IP pings
	 */	 
	function DoCheckIpPings($iTimeToWait)
	{
		$sIp = $this->Get('ip')->ToString();
		$sSystemType = strtoupper(php_uname($mode = "s"));
		if (strpos($sSystemType, 'WIN') === false)
		{
			// Unix type - what else?
			$sCommand = "ping -c ".NUMBER_OF_PINGS." -W ".($iTimeToWait*1000)." ".$sIp;
		}
		else
		{
			// Windows
			$sCommand = "ping -n ".NUMBER_OF_PINGS." -w ".($iTimeToWait*1000)." ".$sIp;
		}
		exec($sCommand, $aOutput, $iRes);
		if ($iRes == 0)
		{
			// IP pings
			$aOutput[0] = $sCommand;
			return $aOutput;  
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Displays the tabs listing the parent objects
	 */
	function DisplayBareRelations(WebPage $oPage, $bEditMode = false)
	{
		$sOrgId = $this->Get('org_id');
		if ($sOrgId != null)
		{
			// Execute parent function first 
			parent::DisplayBareRelations($oPage, $bEditMode);
		}
	}
	
	/**
	 * Check validity of new IP attributes before creation
	 */
	public function DoCheckToWrite()
	{
		// Run standard iTop checks first
		$sParentCheck = parent::DoCheckToWrite();
		if ($sParentCheck != '')
		{
			$this->m_aCheckIssues[] = $sParentCheck;
			return;
		}
		
		// For new IPs only: 
		if ($this->IsNew())
		{
			$sOrgId = $this->Get('org_id');
			$oIp = $this->Get('ip');
			$sIp = $oIp->ToString();
			
			// Make sure IP doesn't already exist for creation
			$oIpSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv6Address AS i WHERE i.ip = :ip AND i.org_id = $sOrgId",  array('ip' => $sIp)));
			while ($oIpAdd = $oIpSet->Fetch())
			{
				// It's a creation -> deny it
				$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPAddress:IPCollision');
				return;
			}
			
			// If IP Range is selected, make sure IP belongs to range
			$iIpRangeId = $this->Get('range_id');
			if ($iIpRangeId != null)
			{
				$oIpRange = MetaModel::GetObject('IPv6Range', $iIpRangeId, true /* MustBeFound */);
				if (!$oIpRange->DoCheckIpInRange($oIp))
				{
					$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPAddress:NotInRange');
					return;
				}
			}
			// If not, make sure IP belongs to subnet
			else
			{
				$iSubnetId = $this->Get('subnet_id');
				if ($iSubnetId != 0)
				{
					$oSubnet = MetaModel::GetObject('IPv6Subnet', $iSubnetId, true /* MustBeFound */);
					if (!$oSubnet->DoCheckIpInSubnet($oIp))
					{
						$this->m_aCheckIssues[] = Dict::Format('UI:IPManagement:Action:New:IPAddress:NotInSubnet');
						return;
					}
				}
				else
				{
					// Look for subnet that IP may belong to
					$oSubnetSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT IPv6Subnet AS s WHERE s.ip <= :ip AND :ip <= s.lastip AND s.org_id = $sOrgId",  array('ip' => $sIp)));
					if ($oSubnetSet->Count() != 0)
					{
						$oSubnet = $oSubnetSet->Fetch();
						$this->Set('subnet_id', $oSubnet->GetKey());
					}
					// Else there is no subnet where the IP can be attacehd too -> ophean IP
				}
			}
			
			// If required by global parameters, ping IP before assigning it 
			$sPingBeforeAssign = utils::ReadPostedParam('attr_ping_before_assign', '');
			if (empty($sPingBeforeAssign))
			{
				$sPingBeforeAssign = GetFromGlobalIPConfig('ping_before_assign', $sOrgId);
			}
			if ($sPingBeforeAssign =='ping_yes')
			{
				$aOutput = $this->DoCheckIpPings(TIME_TO_WAIT_FOR_PING_LONG);
				if (!empty($aOutput))
				{
					$sOutput = '';
					foreach ($aOutput as $line)
					{
						$sOutput .= $line."    ";
					}
					$this->m_aCheckIssues[] = Dict::S('UI:IPManagement:Action:New:IPAddress:IPPings').$sOutput;
					return;
				}
			}
		}
	}
	
	/**
	 * Change flag of attributes that shouldn't be modified beside creation.
	 */
	public function GetAttributeFlags($sAttCode, &$aReasons = array(), $sTargetState = '')
	{
		if ((!$this->IsNew()) && ($sAttCode == 'ip'))
		{
			return OPT_ATT_READONLY;
		}
		return parent::GetAttributeFlags($sAttCode, $aReasons, $sTargetState);
	}
}
