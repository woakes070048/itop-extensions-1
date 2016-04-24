<?php
class CustomServices implements iRestServiceProvider
{
    public function ListOperations($sVersion)
    {
        $aOps = array();
        if (in_array($sVersion, array('1.3')))
        {
            $aOps[] = array(
                'verb' => 'core/trigger',
                'description' => 'Trigger'
            );
        }
        return $aOps;
    }

    public function ExecOperation($sVersion, $sVerb, $aParams)
    {
        $oResult = new RestResultWithObjects();
        switch ($sVerb)
        {
            case 'core/trigger':
                RestUtils::InitTrackingComment($aParams);
                $sClass = RestUtils::GetClass($aParams, 'class');
                $key = RestUtils::GetMandatoryParam($aParams, 'key');
                $type = RestUtils::GetMandatoryParam($aParams, 'type');
                $oObject = RestUtils::FindObjectFromKey($sClass, $key);

                $aClasses = MetaModel::EnumParentClasses($sClass, ENUM_PARENT_CLASSES_ALL);
                $sClassList = implode(", ", CMDBSource::Quote($aClasses));

                $oResult = new RestResult();
                switch ($type) {
                    case 'portal':
                        $oSet = new DBObjectSet(DBObjectSearch::FromOQL("SELECT TriggerOnPortalUpdate AS t WHERE t.target_class IN ($sClassList)"));
                        while ($oTrigger = $oSet->Fetch())
                        {
                            $oTrigger->DoActivate($oObject->ToArgs('this'));
                        }
                        $oResult->triggered = true;
                        break;

                    default:
                        $oResult->code = RestResult::INTERNAL_ERROR;
                        $oResult->message = "Invalid type: '$type'";
                        break;
                }
                break;

            default:
        }
        return $oResult;
    }
}
?>
