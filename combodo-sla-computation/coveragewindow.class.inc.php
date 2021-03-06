<?php
//
// File generated by ... on the 2014-01-14T15:37:33+0100
// Please do not edit manually
//

/**
 * Open hours definition: start time and end time for each day of the week
 */
class _CoverageWindow_ extends cmdbAbstractObject
{
	/**
	 * Convert the old format (decimal) to the new mandatory format NN:NN	
	 */
	public function Get($sAttCode)
	{
		static $sAttToConvert = '|monday_start|monday_end|tuesday_start|tuesday_end|wednesday_start|wednesday_end|thursday_start|thursday_end|friday_start|friday_end|saturday_start|saturday_end|sunday_start|sunday_end|';

		$sValue = parent::Get($sAttCode);
		if (strstr($sAttToConvert, $sAttCode) !== false)
		{
			// The requested attribute is one of the conversion candidates
			if (!preg_match('/'.COVERAGE_TIME_REGEXP.'/', $sValue))
			{
				// The format does not match the new convention
				// => Convert the decimal value into "hh:mm"
				$fTime = (float) $sValue;
				if ($sValue != '')
				{
					$iHour = floor($fTime);
					$iMin = floor(60 * ($fTime - $iHour));
					if ($iHour > 23)
					{
						$sValue = '24:00';
					}
					else
					{
						$sValue = sprintf('%02d:%02d', $iHour, $iMin);
					}
				}
				else
				{
					$sValue = '00:00';
				}
				$this->Set($sAttCode, $sValue); // so that it gets recorded
			}
		}
		return $sValue;
	}

	/**
	 * Whatever the format in DB, Get as a decimal value	
	 */
	public function GetAsDecimal($sAttCode)
	{
		$sTime = $this->Get($sAttCode);
		$iHour = (int) substr($sTime, 0, 2);
		$iMin = (int) substr($sTime, -2);
		$fTime = (float) $iHour + $iMin / 60;
		return $fTime;
	}

	/**
	 * Get the date/time corresponding to a given delay in the future from the present,
	 * considering only the valid (open) hours as specified by the CoverageWindow object and the given
	 * set of Holiday objects.
	 * @param $oHolidaysSet DBObjectSet The list of holidays to take into account
	 * @param $iDuration integer The duration (in seconds) in the future
	 * @param $oStartDate DateTime The starting point for the computation
	 * @return DateTime The date/time for the deadline
	 */
	public function GetDeadline(DBObjectSet $oHolidaysSet, $iDuration, DateTime $oStartDate)
	{
		$aHolidays2 = array();
		while($oHoliday = $oHolidaysSet->Fetch())
		{
			$aHolidays2[$oHoliday->Get('date')] = $oHoliday->Get('date');
		}

		$oCurDate = clone $oStartDate;
		$iCurDuration = 0;
		$idx = 0;
		do
		{
			// Move forward by one interval and check if we meet the expected duration
			$aInterval = $this->GetNextInterval2($oCurDate, $aHolidays2);
			$idx++;
			if ($aInterval != null)
			{
				$iIntervalDuration = $aInterval['end']->format('U') - $aInterval['start']->format('U'); // TODO: adjust for Daylight Saving Time change !
				if ($oStartDate > $aInterval['start'])
				{
					$iIntervalDuration = $iIntervalDuration - ($oStartDate->format('U') - $aInterval['start']->format('U')); // TODO: adjust for Daylight Saving Time change !
				}
				$iCurDuration += $iIntervalDuration;
				$oCurDate = $aInterval['end'];
			}
			else
			{
				$iIntervalDuration = null; // No more interval, means that the interval extends infinitely... (i.e 24*7)
			}
		}
		while( ($iIntervalDuration !== null) && ($iDuration > $iCurDuration) );
		
		$oDeadline = clone $oCurDate;
		$oDeadline->modify( '+'.($iDuration - $iCurDuration).' seconds');			
		return $oDeadline;		
	}

	/**
	 * Helper function to get the date/time corresponding to a given delay in the future from the present,
	 * considering only the valid (open) hours as specified by the CoverageWindow and the given
	 * set of Holiday objects.
	 * @param $oHolidaysSet DBObjectSet The list of holidays to take into account
	 * @param $oStartDate DateTime The starting point for the computation (default = now)
	 * @param $oEndDate DateTime The ending point for the computation (default = now)
	 * @return integer The duration (number of seconds) of open hours elapsed between the two dates
	 */
	public function GetOpenDuration($oHolidaysSet, $oStartDate, $oEndDate)
	{
		$aHolidays2 = array();
		while($oHoliday = $oHolidaysSet->Fetch())
		{
			$aHolidays2[$oHoliday->Get('date')] = $oHoliday->Get('date');
		}

		$oCurDate = clone $oStartDate;
		$iCurDuration = 0;
		$idx = 0;
		do
		{
			// Move forward by one interval and check if we reach the end date
			$aInterval = $this->GetNextInterval2($oCurDate, $aHolidays2);
			if ($aInterval != null)
			{
				if ($aInterval['start']->format('U') > $oEndDate->format('U'))
				{
					// Interval starts after the end of the period, finished
					$oCurDate = clone $aInterval['start'];
				}
				else
				{
					if ($aInterval['start']->format('U') < $oStartDate->format('U'))
					{
						// First interval, starts before the specified period
						$iStart = $oStartDate->format('U');
					}
					else
					{
						// Not the first interval, starts within the specified period
						$iStart = $aInterval['start']->format('U');
					}
					if ($aInterval['end']->format('U') > $oEndDate->format('U'))
					{
						// Last interval, ends after the specified period
						$iEnd = $oEndDate->format('U');
					}
					else
					{
						// Not the last interval, ends within the specified period
						$iEnd = $aInterval['end']->format('U');
					}
//$sStart = date('Y-m-d H:i:s', $iStart);
//$sEnd = date('Y-m-d H:i:s', $iEnd);
//echo "<p>Adding: ".($iEnd - $iStart)." s [$sStart ; $sEnd]</p>";

					$iCurDuration += $iEnd - $iStart;
					$oCurDate = clone $aInterval['end'];
				}
			}
			else
			{
					$oCurDate = clone $oEndDate;
			}
//echo "<p>\$idx: $idx \$oCurDate: ".($oCurDate->format('Y-m-d H:i:s'))."</p>";
			$idx++;
		}
		while( ($aInterval != null) && ($oCurDate->format('U') < $oEndDate->format('U')));
//echo "<p>\$aInterval != null returned:".($aInterval != null)."</p>";
//echo "<p>\$(\$oCurDate->format('U') < \$oEndDate->format('U') returned:".($oCurDate->format('U') < $oEndDate->format('U'))."</p>";
		
//echo "<p>TOTAL (open hours) duration: ".$iCurDuration." s [".$oStartDate->format('Y-m-d H:i:s')." ; ".$oEndDate->format('Y-m-d H:i:s')."]</p>";
		return $iCurDuration;		
	}

	/////////////////////////////////////////////////////////////////////////////
		
	/**
	 * Helper to compute GetDeadline and GetOpenDuration	
	 */	
	protected function GetNextInterval2($oStartDate, $aHolidays)
	{
		$oStart = clone $oStartDate;

		$sPHPTimezone = MetaModel::GetConfig()->Get('timezone');
		if ($sPHPTimezone != '')
		{
			$oTZ = new DateTimeZone($sPHPTimezone);
			$oStart->SetTimeZone($oTZ);
		}
		$oStart->SetTime(0, 0, 0);
		
		$oEnd = clone $oStart;
		if ($this->IsHoliday($oStart, $aHolidays))
		{
			// do nothing, start = end: the interval is of no duration... will be skipped
		}
		else
		{
			$iWeekDay = $oStart->format('w');
			$aData = $this->GetOpenHours($iWeekDay);
			$this->ModifyDate($oStart, $aData['start']);
			$this->ModifyDate($oEnd, $aData['end']);
		}

		if ($oStartDate->format('U') >= $oEnd->format('U'))
		{
			// Next day
			$oStart = clone $oStartDate;
			if ($sPHPTimezone != '')
			{
				$oTZ = new DateTimeZone($sPHPTimezone);
				$oStart->SetTimeZone($oTZ);
			}
			$oStart->SetTime(0, 0, 0);
			$oStart->modify('+1 day');
			$oEnd = clone $oStart;
			if ($this->IsHoliday($oStart, $aHolidays))
			{
				// do nothing, start = end: the interval is of no duration... will be skipped
			}
			else
			{
				$oStart = clone $oStartDate;
				if ($sPHPTimezone != '')
				{
					$oTZ = new DateTimeZone($sPHPTimezone);
					$oStart->SetTimeZone($oTZ);
				}
				$oStart->SetTime(0, 0, 0);
				$oStart->modify('+1 day');
				$oEnd = clone $oStart;
				$iWeekDay = $oStart->format('w');
				$aData = $this->GetOpenHours($iWeekDay);
				$this->ModifyDate($oStart, $aData['start']);
				$this->ModifyDate($oEnd, $aData['end']);
			}
		}
		return array('start' => $oStart, 'end' => $oEnd);
	}
	
	/**
	 * Modify a date by a (floating point) number of hours (e.g. 11.5 hours for 11 hours and 30 minutes)
	 * @param $oDate DateTime The date to modify
	 * @param $fHours number Number of hours to offset the date
	 */
	protected function ModifyDate(DateTime $oDate, $fHours)
	{
		$iStartHour = floor($fHours);
		if ($iStartHour != $fHours)
		{
			$iStartMinutes = floor(($fHours - $iStartHour)*60);
			$oDate->modify("+ $iStartMinutes minutes");
		}
		$oDate->modify("+ $iStartHour hours");
	}
	
	protected function GetOpenHours($iDayIndex)
	{
		static $aWeekDayNames = array(0 => 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
		$sDayName = $aWeekDayNames[$iDayIndex];
		return array(
			'start' => $this->GetAsDecimal($sDayName.'_start'),
			'end' => $this->GetAsDecimal($sDayName.'_end')
		);
	}
	
	protected function IsHoliday($oDate, $aHolidays)
	{
		$sDate = $oDate->format('Y-m-d');
		
		if (isset($aHolidays[$sDate]))
		{
			// Holiday found in the calendar
			return true;
		}
		else
		{
			// No such holiday in the calendar
			return false;
		}
	}
	
	public function IsInsideCoverage($oCurDate, $oHolidaysSet = null)
	{
		if ($oHolidaysSet != null)
		{
			$aHolidays = array();
			while($oHoliday = $oHolidaysSet->Fetch())
			{
				$aHolidays[$oHoliday->Get('date')] = $oHoliday->Get('date');
			}
			// Today's holiday! Not considered inside the coverage
			if ($this->IsHoliday($oCurDate, $aHolidays)) return false;
		}
		
		// compute today's limits for the coverage
		$aData = $this->GetOpenHours($oCurDate->format('w'));
		$oStart = clone $oCurDate;
		$sPHPTimezone = MetaModel::GetConfig()->Get('timezone');
		if ($sPHPTimezone != '')
		{
			$oTZ = new DateTimeZone($sPHPTimezone);
			$oStart->SetTimeZone($oTZ);
		}
		$oStart->SetTime(0, 0, 0);
		$oEnd = clone $oStart;
		$this->ModifyDate($oStart, $aData['start']);
		$this->ModifyDate($oEnd, $aData['end']);
		
		// Check if the given date is inside the limits
		$iCurDate = $oCurDate->format('U');
		if( ($iCurDate > $oStart->format('U')) && ($iCurDate <= $oEnd->format('U')) ) return true;
		
		// Outside of the coverage
		return false;
	}
}
