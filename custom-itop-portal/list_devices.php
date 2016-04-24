<?php
/*
 Include at top in `portal/index.php`:
  require_once(APPROOT.'/extensions/custom-itop-portal/list_devices.php');

 Add menu entry in @DisplayMainMenu:
  $oP->AddMenuButton('showdevices', 'Portal:ShowDevices', '../portal/index.php?operation=show_devices');

 Add this to main loop switch case:
  case 'show_devices':
    DisplayMainMenu($oP);
    ShowDevices($oP);
    break;
 */

/**
 * Displays company devices
 * @param WebPage $oP The current web page
 * @return void
 */
function ShowDevices(WebPage $oP)
{
    $oP->add("<div id=\"my_devices\">\n");
    $oP->add("<h1 id=\"title_my_devices\">".Dict::S('Portal:MyDevices')."</h1>\n");
    ListDevices($oP);
    $oP->add("</div>\n");
}

/**
 * Lists all company devices
 * @param WebPage $oP The current web page
 * @return void
 */
function ListDevices(WebPage $oP, $class = "NetworkDevice")
{
    $aAttSpecs = explode(',', PORTAL_DEVICES_SEARCH_CRITERIA);

    $aClassToSet = array();
    $oUserOrg = GetUserOrg();
    $oP->DisplaySearchForm($class, $aAttSpecs, array(), 'device_search_', false);
    $oSearch = $oP->PostedParamsToFilter($class, $aAttSpecs, 'device_search_');
    if(is_null($oSearch))
    {
        $oSearch = new DBObjectSearch($class);
    }
    $oSearch->AddCondition('org_id', $oUserOrg->GetKey());
    $aClassToSet[$class] = new CMDBObjectSet($oSearch);

    DisplayDeviceLists($oP, $aClassToSet);
}

function DisplayDeviceLists(WebPage $oP, $aClassToSet)
{
    $iNotEmpty = 0; // Count of types for which there are some items to display
    foreach ($aClassToSet as $sClass => $oSet)
    {
        if ($oSet->Count() > 0)
        {
            $iNotEmpty++;
        }
    }
    if ($iNotEmpty == 0)
    {
        $oP->p(Dict::S('Portal:NoDevices'));
    }
    else
    {
        foreach ($aClassToSet as $sClass => $oSet)
        {
            if ($iNotEmpty > 1)
            {
                // Differentiate the sublists
                $oP->add("<h2>".MetaModel::GetName($sClass)."</h2>\n");
            }
            if ($oSet->Count() > 0)
            {
                $sZList = GetConstant($sClass, 'LIST_ZLIST');
                $aZList =  explode(',', $sZList);
                $oP->DisplaySet($oSet, $aZList, Dict::S('Portal:NoDevices'));
            }
        }
    }
}
?>
