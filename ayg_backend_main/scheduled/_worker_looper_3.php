<?php
$_SERVER['REQUEST_METHOD']='';
$_SERVER['REMOTE_ADDR']='';
$_SERVER['REQUEST_URI']='';
$_SERVER['SERVER_NAME']='';

ini_set("memory_limit","384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);
define("WORKER_MENU_LOADER", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


//register_shutdown_function( "shutdown_handler_menu_loader" );

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

///////////////////////////////////////////////////////////////////////////////
// Check if 9001 request came in
///////////////////////////////////////////////////////////////////////////////
if(!empty(getCacheAPI9001Status())) {

	$nullValue = '';
	shutdownProcess();
}

///////////////////////////////////////////////////////////////////////////////

if(strcasecmp($GLOBALS['env_InHerokuRun'], "Y")!=0) {


}

$gapInSeconds = 30*60; // 30 minutes
while(1>0) {

	// Check cache to check if it is time to process
	while(1>0) {

		// TAG
		///////////////////////////////////////////////////////////////////////////////
		// Check if 9001 request came in
		///////////////////////////////////////////////////////////////////////////////
		if(!empty(getCacheAPI9001Status())) {

			shutdownProcess();
		}

		if(isTimeToRunMenuLoader($gapInSeconds)
			|| strcasecmp($GLOBALS['env_InHerokuRun'], "Y")!=0) {

			break;
		}

		// Sleep for 60 seconds
		s3logMenuLoader(printLogTime() . "Sleeping..." . "\r\n", true);
		sleep(60);
	}

	// TAG
	// use version or create new one
    list($inactiveTimestamp, $resumeRun) = getMenuLoaderVersion();
    setMenuLoaderLastVersion($inactiveTimestamp);

    $inactiveTimestamp = intval($inactiveTimestamp);
	///////////////////////////////////////////////////////////////////////////////
	// Check if 9001 request came in
	///////////////////////////////////////////////////////////////////////////////
	if(!empty(getCacheAPI9001Status())) {

		shutdownProcess();
	}

    $retailerItemCategoriesNew = [];
    $retailerItemCategoriesKnown = [];

	s3logMenuLoader("\r\n\r\n");

    if($resumeRun) {

    	s3logMenuLoader(printLogTime() . "Resuming Run - " . $inactiveTimestamp . "\r\n");
    }

    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Generating known RetailerItemCategories list" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

    $objectParseQueryRetailerItemCategories = parseExecuteQuery([], "RetailerItemCategories");
	foreach($objectParseQueryRetailerItemCategories as $category) {

		$retailerItemCategoriesKnown[] = $category->get('categoryName');
	}

	// Only pull those that are NOT on hold continousPingCheck = true
	$objectParseQueryPOSConfig = parseExecuteQuery(array("automatedMenuPull" => true, "continousPingCheck" => true), "RetailerPOSConfig", "", "", array("retailer", "retailer.location", "dualPartnerConfig"));
    s3logMenuLoader(printLogTime() . "Starting Version (" . $inactiveTimestamp . ") for " . count_like_php5($objectParseQueryPOSConfig) . " retailers" . "\r\n");


	foreach($objectParseQueryPOSConfig as $posConfig) {
	
	    // TAG
	    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName'], 20, 0, true);

		$retailerUniqueId = $posConfig->get('retailer')->get('uniqueId');
		$dualPartnerConfig = ($posConfig->has('dualPartnerConfig')) ? $posConfig->get('dualPartnerConfig') : '';
		$retailerInfo = getRetailerInfo($retailerUniqueId);
		// TAG
		$retailerDirectoryName = getRetailerS3MenuDirectoryName($retailerInfo);
		$retailerInfoForDisplay = getRetailerInfoForDisplay($retailerInfo);

		$airportIataCode = $posConfig->get('retailer')->get('airportIataCode');
	    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	    s3logMenuLoader(printLogTime() . "Retailer (" . $retailerInfoForDisplay . ") - " . $retailerUniqueId . "\r\n");
	    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

	    // check if this retailer is to be skipped
	    // 1  = Fully Loaded
	    // -1 = Failed last run
	    // 0  = Never run
	    $statusOfRun = getStatusMenuLoadedForVersionForRetailer($inactiveTimestamp, $retailerUniqueId);
	    if($statusOfRun == 1) {

	    	s3logMenuLoader(printLogTime() . "Skipping (found in rerun log)" . "\r\n");
	    	continue;
	    }
	    else if($statusOfRun == -1) {

	    	s3logMenuLoader(printLogTime() . "Skipping (FAILED last run)" . "\r\n");
	    	continue;
	    }


	    // Store version process started for retailer
	    setMenuLoaderVersionForRetailer($inactiveTimestamp, $retailerUniqueId, -1);

		// HMSHost
	    // TAG
        /*
		if(!empty($dualPartnerConfig) && strcasecmp($dualPartnerConfig->get('partner'), 'hmshost')==0) {

			// TAG
			$partner = $dualPartnerConfig->get('partner');
			$propertyId = $dualPartnerConfig->get('airportId');
			$revenueCenterId = $dualPartnerConfig->get('retailerId');
			$partner = $dualPartnerConfig->get('partner');

			if(!isset($hmshost)) {

				$hmshost = new HMSHost($propertyId, $revenueCenterId, $retailerUniqueId, 'menu');
			}
			// TAG
			else {

				$hmshost->setPropertyId($propertyId);
				$hmshost->setRevenueCenterId($revenueCenterId);
				$hmshost->setUniqueRetailerId($retailerUniqueId);
			}

			// Load file to 3rd Party Table
		    s3logMenuLoader(printLogTime() . "Loading 3rd party table with exiting customizable file" . "\r\n");
			
		    try {

				$customizableFileLoad = $hmshost->load_menu_item_customizable_file_to_db(getS3KeyPath_RetailerMenuFiles($airportIataCode, $partner, $retailerDirectoryName), $retailerUniqueId);

				// File loaded, set cache to last load time
				if($customizableFileLoad) {

					setMenuLoaderCustomizableLoadTime($retailerUniqueId, time());
				}
				else {

				    s3logMenuLoader(printLogTime() . "Skipped loading 3rd party table with exiting customizable file as no change detected" . "\r\n");
				}
		    }
		    catch (Exception $ex) {

		    	s3logMenuLoader(printLogTime() . "Failed - " . $ex->getMessage() . "\r\n");
		    }

			// Pull menu
		    s3logMenuLoader(printLogTime() . "Pulling Menu from HMSHost" . "\r\n");
			
		    try {

		    	$hmshost->menu_modifiers_pull();
				$hmshost->menu_pull();
		    }
		    catch (Exception $ex) {

		    	s3logMenuLoader(printLogTime() . "Menu pull failed - " . $ex->getMessage());
		    	continue;
		    }

		    s3logMenuLoader(printLogTime() . "Formatting the menu for compare" . "\r\n");
		    // TAG
			list($newMenu, $itemsSkipped) = $hmshost->menu_extract($GLOBALS['__menuLoaderConfig'][$partner]['itemCategoriesNotAllowedThruSecurity'], $GLOBALS['__menuLoaderConfig'][$partner]['unallowedItems'], $GLOBALS['__menuLoaderConfig'][$partner]['unallowedItemsThruSecurityKeywords']);
		    s3logMenuLoader(printLogTime() . "Partner Menu has " . count_like_php5($newMenu) . " items" . "\r\n");

			// Load 3rd Party table
		    s3logMenuLoader(printLogTime() . "Loading 3rd party table with any new items" . "\r\n");
			 $rows_loaded = $hmshost->menu_initial_load($newMenu, $itemsSkipped, $retailerUniqueId);
		     s3logMenuLoader(printLogTime() . $rows_loaded . " new items loaded" . "\r\n");

			// Create updated 3rd party file
			if($rows_loaded > 0) {

			    s3logMenuLoader(printLogTime() . "Generating customizable file" . "\r\n");
				$hmshost->menu_item_customizable_to_file(getS3KeyPath_RetailerMenuFiles($airportIataCode, $partner, $retailerDirectoryName), $retailerUniqueId);	
			}
			else {

			    s3logMenuLoader(printLogTime() . "Skipping generating new customizable file" . "\r\n");
			}

			// Create menu files for reference
		    s3logMenuLoader(printLogTime() . "Creating menu files for reference" . "\r\n");
			$hmshost->menu_initial_load_to_file(getS3KeyPath_RetailerMenuFiles($airportIataCode, $partner, $retailerDirectoryName), $newMenu);

			unset($itemsSkipped);
		}

		// TAG
		// Internal
		else if(empty($dualPartnerConfig)) {
            die('eee');
		    s3logMenuLoader(printLogTime() . "Skipping retailer (identified as non-partner retailer)" . "\r\n");continue;
		}
        */
		// Pull menu from DB
	    s3logMenuLoader(printLogTime() . "Pulling 3rd party table" . "\r\n");

		$objectParseQueryRetailerItems3rdPartyApprovals = parseExecuteQuery(array("uniqueRetailerId" => $retailerUniqueId), "RetailerItems3rdPartyApprovals");
		foreach($objectParseQueryRetailerItems3rdPartyApprovals as $object) {

			$retailerItems3rdPartyApprovals[$object->get("itemId")] = $object;
			$retailerItems3rdPartyApprovals[$object->get("uniqueId")] = $object;
		}

		// Pull menu from DB
	    s3logMenuLoader(printLogTime() . "Pulling existing in db menu" . "\r\n");
		$menuInDb = pullMenuForCompare($retailerUniqueId);


		// should be no changes
        $newMenu=$menuInDb;
        $retailerItems3rdPartyApprovals=[];


        // Compare menu and update in DB
	    s3logMenuLoader(printLogTime() . "Comparing menus - Version: " . $inactiveTimestamp . "\r\n");
		list($itemsTo86, $itemsToUn86, $itemsToInsert, $itemsToUpdate, $itemsToDelete, $newMenu) = compareMenusForLoading($newMenu, $menuInDb, $retailerUniqueId, $partner, $inactiveTimestamp, $retailerItems3rdPartyApprovals, $objectParseQueryRetailerItems3rdPartyApprovals);
		// print_r(compareMenusForLoading($newMenu, $menuInDb, $retailerUniqueId, $partner, $inactiveTimestamp, $retailerItems3rdPartyApprovals, $objectParseQueryRetailerItems3rdPartyApprovals));exit;
		// print_r($newMenu);
		// echo("<br /><br />-----------");

		// print_r($menuInDb);
		// exit;

		// 86 items
	    if(count_like_php5($itemsTo86) > 0) {

	        s3logMenuLoader(printLogTime() . "- 86 items (" . count_like_php5($itemsTo86) . ")" . "\r\n");		
	        $count = menuLoader86Items($itemsTo86, $retailerInfo);
	        s3logMenuLoader(printLogTime() . "- Added 86 for " . $count . " items" . "\r\n");
	    }

		// Un 86 items
	    if(count_like_php5($itemsToUn86) > 0) {

	        s3logMenuLoader(printLogTime() . "- Remove 86 items (" . count_like_php5($itemsToUn86) . ")" . "\r\n");		
	        $count = menuLoaderUn86Items($itemsToUn86, $retailerInfo);
	        s3logMenuLoader(printLogTime() . "- Removed 86 for " . $count . " items" . "\r\n");
	    }

	    // Menu update stats
    	$menuUpdated = false;
	    if(count_like_php5($itemsToDelete) > 0 ||
			count($itemsToUpdate) > 0 ||
			count($itemsToInsert) > 0) {

	    	$menuUpdated = true;
		    notifyOnSlackMenuUpdates($retailerInfo["uniqueId"], "Menu to Update (Automated)", ["Insert" => count_like_php5($itemsToInsert), "Update" => count_like_php5($itemsToUpdate), "Delete" => count_like_php5($itemsToDelete)]);
		}

	    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	    s3logMenuLoader(printLogTime() . "To Delete: " . count_like_php5($itemsToDelete) . "\r\n");
	    s3logMenuLoader(printLogTime() . "To Update: " . count_like_php5($itemsToUpdate) . "\r\n");
	    s3logMenuLoader(printLogTime() . "To Insert: " . count_like_php5($itemsToInsert) . "\r\n");
	    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");


	    die('ee');
		// Store current close level of POS
	    $retailerWasClosedBeforeMenuUpdate = getRetailerOpenAfterClosedEarly($retailerInfo["uniqueId"]);

	    // Check if we need to close the retailer
	    $isRetailerClosed = false;
	    if(count_like_php5($itemsToDelete) > 0
	        || count_like_php5($itemsToUpdate) > 0) {

	        s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	        s3logMenuLoader(printLogTime() . "Closing Retailer (" . $retailerInfoForDisplay . ")" . "\r\n");
	        s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");        

	        setRetailerClosedEarlyTimerMessage($retailerInfo["uniqueId"], getTabletOpenCloseLevelFromSystem(), "EOD", true); 

	        $isRetailerClosed = true;
	    }

	    /////////////////////////////////////////////////////
	    /////////////////////////////////////////////////////
	    // Begin updating                                  //
	    /////////////////////////////////////////////////////
	    /////////////////////////////////////////////////////

	    if(count_like_php5($itemsToDelete) > 0) {

	        updateMenuDeleteItems($retailerUniqueId, $itemsToDelete, $inactiveTimestamp);

		    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
		    s3logMenuLoader(printLogTime() . "Deleting Items Complete" . "\r\n");
		    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	    }

	    if(count_like_php5($itemsToUpdate) > 0) {
	        
	        $isRetailerClosed = updateMenuUpdateItems($retailerUniqueId, $itemsToUpdate, $newMenu, $partner, $inactiveTimestamp, $airportIataCode, $retailerDirectoryName, $retailerInfo, $isRetailerClosed, $retailerItems3rdPartyApprovals, $objectParseQueryRetailerItems3rdPartyApprovals);

		    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
		    s3logMenuLoader(printLogTime() . "Updating Items Complete" . "\r\n");
		    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	    }

	    if(count_like_php5($itemsToInsert) > 0) {
	        
	        $isRetailerClosed = updateMenuInsertItems($retailerUniqueId, $itemsToInsert, $newMenu, $partner, $airportIataCode, $retailerDirectoryName, $retailerInfo, $isRetailerClosed, $retailerItems3rdPartyApprovals, $objectParseQueryRetailerItems3rdPartyApprovals);
	    }

	    // Menu updating complete, open retailer
	    if($isRetailerClosed == true) {

	        s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	        s3logMenuLoader(printLogTime() . "Reopening Retailer (" . $retailerInfoForDisplay . ")" . "\r\n");
	        s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");        

	        if(canRetailerOpenAfterClosedEarly($retailerInfo["uniqueId"], getTabletOpenCloseLevelFromSystem())) {

	            // TAG
	            setRetailerOpenAfterClosedEarly($retailerInfo["uniqueId"], getTabletOpenCloseLevelFromSystem()); 
	        }

	        s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	        s3logMenuLoader(printLogTime() . "Reopened Retailer (" . $retailerInfoForDisplay . ")" . "\r\n");
	        s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
	
	        $isRetailerClosed = false;

	        // Retailer Was closed before we force closed it too
	        // So let's close it again with the original level
	        if(is_array($retailerWasClosedBeforeMenuUpdate) && $retailerWasClosedBeforeMenuUpdate[1] != getTabletOpenCloseLevelFromSystem()) {
	        	// TAG
	            setRetailerClosedEarlyTimerMessage($retailerInfo["uniqueId"], $retailerWasClosedBeforeMenuUpdate[1]);
	        }
	    }

	    // Retailer processing complete
	    s3logMenuLoader(printLogTime() . "Generating Retailer Items Categories review" . "\r\n");
	    // Identify new RetailerItemCategories
		$objectParseQueryRetailerItems = parseExecuteQuery(array("uniqueId" => $retailerUniqueId), "RetailerItems");

		$i = count_like_php5($retailerItemCategoriesNew);
		foreach($objectParseQueryRetailerItems as $retailerItem) {

			if($retailerItem->has('itemCategoryName')
				&& !in_array($retailerItem->get('itemCategoryName'), $retailerItemCategoriesKnown)
				&& !in_array($retailerItem->get('itemCategoryName'), $retailerItemCategoriesNew)) {

				$i++;
				$retailerItemCategoriesNew["Item " . $i] = $retailerItem->get('itemCategoryName');
			}
		}

		// TAG
		$i = count_like_php5($retailerItemCategoriesNew);
		foreach($newMenu as $retailerItem) {

			if(!in_array($retailerItem["itemCategoryName"], $retailerItemCategoriesKnown) && !in_array($retailerItem["itemCategoryName"], $retailerItemCategoriesNew)) {

				// TAG
				$i++;
				$retailerItemCategoriesNew["Item " . $i] = $retailerItem["itemCategoryName"];
			}
		}

		// Pending items
	    s3logMenuLoader(printLogTime() . "Generating Pending Items review" . "\r\n");
		// TAG
		$pendingItems = [];
		$i = 0;

		foreach($objectParseQueryRetailerItems3rdPartyApprovals as $retailerItem) {

			if($retailerItem->get('reviewed') == false) {

				$i++;
				$pendingItems["Item " . $i] = $retailerItem->get('itemPOSName');
			}
		}

	    s3logMenuLoader(printLogTime() . count_like_php5($pendingItems) . " Pending Items to review" . "\r\n");

	    if(count_like_php5($pendingItems) > 0) {

	    	$hash = count_like_php5($pendingItems);
	    	$hashCache = intval(getMenuLoaderPendingHash($retailerUniqueId));

	    	if($hash != $hashCache) {

		        notifyOnSlackMenuUpdates($retailerUniqueId, "Items pending review", ["Number of Items" => count_like_php5($pendingItems)]);

				setMenuLoaderPendingHash($retailerUniqueId, $hash);
	    	}
	    }

        // Reset cache
        $cacheKeyList = [];
        if($menuUpdated == true) {

			$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItems*");
			$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemModifiers*");
			$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemModifierOptions*");
			$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemProperties*");
			$cacheKeyList[]  = $GLOBALS['redis']->keys("NRC__menu*" . $retailerUniqueId);

			// TAG
			// TAG
		    s3logMenuLoader(printLogTime() . "Resetting cache" . "\r\n");

			resetCache($cacheKeyList);
		    s3logMenuLoader(printLogTime() . "Reset cache" . "\r\n");
        }

	    // Store version procesed for retailer
	    setMenuLoaderVersionForRetailer($inactiveTimestamp, $retailerUniqueId, $inactiveTimestamp);
		// TAG
	    // TAG
		if(isset($workerQueue)) {

			workerQueueConnectionsDisconnect();
			unset($workerQueue);
		}
	}


	// TAG
	// Slack notify new Retailer Item Categories
    if(count_like_php5($retailerItemCategoriesNew) > 0) {

		$hash = strval(count_like_php5($retailerItemCategoriesNew));
		$hashCache = strval(trim(getMenuLoaderNewCategoryHash()));

    	if(strcasecmp($hash, $hashCache)!=0) {

    		// TAG
		    s3logMenuLoader(printLogTime() . "New Category count = " . $hash . ", Found in Cache = " . $hashCache . " - " . intval(trim(getMenuLoaderNewCategoryHash())) . "\r\n");

	        notifyOnSlackMenuUpdates("", "Categories missing from RetailerItemCategories", ["Number of Categories" => $hash]);

	    	setMenuLoaderNewCategoryHash($hash);
		    s3logMenuLoader(printLogTime() . "Writing " . count_like_php5($retailerItemCategoriesNew) . " categories to file" . "\r\n");
	    	menuLoaderWriteNewCategoriesToFile($retailerItemCategoriesNew, getS3KeyPath_RetailerMenuLoaderNewCategories());
	    	// TAG
		    s3logMenuLoader(printLogTime() . "Done writing " . count_like_php5($retailerItemCategoriesNew) . " categories to file" . "\r\n");
    	}

        // TAG
        $retailerItemCategoriesNew = [];
    }

    // Delete version
    s3logMenuLoader(printLogTime() . "Reset run" . "\r\n");
    delMenuLoaderVersion();

    // Delete hash table log of retailer load
    s3logMenuLoader(printLogTime() . "Reset run hash" . "\r\n");
    delMenuLoaderVersionForRetailer($inactiveTimestamp);

	try {

		if(isset($hmshost)) {

			// $hmshost->session_end();
			unset($hmshost);
		}
	}
	catch (Exception $ex) {

	}

	if(strcasecmp($GLOBALS['env_InHerokuRun'], "Y")!=0) {
	
		s3logMenuLoader("---", true);exit;
	}
}

function shutdownProcess() {

	error_log("Shutting down...");

	setCacheAPI9001WorkerLooper3();

	sleep(1);

	while(1>0) {

		// Wait to be shutdown
	}

	// Graceful exit
	// posix_kill(posix_getpid(), 15);

	// exit(0);	
}

?>
