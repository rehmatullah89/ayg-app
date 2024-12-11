<?php

function hasLooperLastRunTimeLimitPassed($lastRunTimestamp, $gapNeededInMins) {

	$timeToCompare = time()-($gapNeededInMins * 60);
	
	if($lastRunTimestamp < $timeToCompare) {
		
		return true;
	}
	
	return false;
}

?>