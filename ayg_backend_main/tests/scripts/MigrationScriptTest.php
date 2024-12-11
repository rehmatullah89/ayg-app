<?php

use App\Consumer\Helpers\ConfigHelper;
use Parse\ParseClient;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../putenv.php';

date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

$scriptsDirectory = __DIR__ . '/../../migrations/scripts';
$demoScriptsDirectory = __DIR__ . '/scripts';

pressEnter('script will check if there is a TestUser table');
test1CheckIfTableDoesNotExists('TestUser');

pressEnter('script will copy first migration scripts (that creates TestUser into scripts directory');
copyFirstScript($demoScriptsDirectory, $scriptsDirectory);

pressEnter('script will run migrations - only new TestUser table should be created');
runMigration();

pressEnter('script will check of table exists');
test2CheckIfTableExists('TestUser');


pressEnter('script will run reverse with step 1 - which should reverse previous step, so delete TestUser table');
runMigrationWithReverse(1);


pressEnter('script will check of table does not exist');
test1CheckIfTableDoesNotExists('TestUser');


pressEnter('script will move all files to script directory (so all migrations can be triggered)');
moveFilesFromDirectoryToDirectory($demoScriptsDirectory, $scriptsDirectory);


pressEnter('script will check if non of the tables [TestUser,TestParseUserClass] exists');
test1CheckIfTableDoesNotExists('TestUser');
test1CheckIfTableDoesNotExists('TestParseUserClass');


pressEnter('script will run migrations, TestUser should be created, TestParseUserClass should be created, TestParseUserClass should be modified, TestParseUserClass should be deleted');
runMigration();


pressEnter('script will check if TestUser exists and TestParseUserClass does not exists');
test2CheckIfTableExists('TestUser');
test1CheckIfTableDoesNotExists('TestParseUserClass');



//reverse last 1 migrations, both tables should exists
pressEnter('script will run reverse with step 1 - which should reverse deleting TestParseUserClass');
runMigrationWithReverse(1);


pressEnter('script will check if both TestUser and TestParseUserClass exists (and show structure)');
test2CheckIfTableExists('TestUser');
test2CheckIfTableExists('TestParseUserClass');

//reverse last 1 migrations, both tables should exists, TestParseUserClass should have different structure
pressEnter('script will run reverse with step 1 - which should reverse changing in TestParseUserClass, which means that its structure should be different');
runMigrationWithReverse(1);


pressEnter('script will check if both TestUser and TestParseUserClass exists (and show structure)');
test2CheckIfTableExists('TestUser');
test2CheckIfTableExists('TestParseUserClass');


pressEnter('script will run reverse without steps parameter - which should reverse all migrations');
// reverse all, both tables should not exists
runMigrationWithReverse(null);

pressEnter('script will check if both tables are deleted');
test1CheckIfTableDoesNotExists('TestUser');
test1CheckIfTableDoesNotExists('TestParseUserClass');


pressEnter('script will run migrations again (All files will be triggered)');
runMigration();

pressEnter('script will check if TestUser exists and TestParseUserClass does not exists');
test2CheckIfTableExists('TestUser');
test1CheckIfTableDoesNotExists('TestParseUserClass');

pressEnter('script will run reverse with step 2 - which should reverse deleting and modifying TestParseUserClass');
runMigrationWithReverse(2);

pressEnter('script will list both tables with parameters');
test2CheckIfTableExists('TestUser');
test2CheckIfTableExists('TestParseUserClass');

pressEnter('script will run migrations again so 2 last migrations should be run');
runMigration();

pressEnter('script will check if TestUser exists and TestParseUserClass does not exists');
test2CheckIfTableExists('TestUser');
test1CheckIfTableDoesNotExists('TestParseUserClass');


pressEnter('script will run reverse without steps parameter - which should reverse all migrations');
runMigrationWithReverse(null);

pressEnter('script will check if both tables are deleted');
test1CheckIfTableDoesNotExists('TestUser');
test1CheckIfTableDoesNotExists('TestParseUserClass');


pressEnter('script will move migrations files back to test directory');
moveFilesFromDirectoryToDirectory($scriptsDirectory, $demoScriptsDirectory);


function pressEnter($string){

    echo "\n---------------";
    echo "\n$string";
    echo " [press Enter to continue]";
    $handle = fopen ("php://stdin","r");
    fgets($handle);
    sleep(1);
    echo "---------------\n\n";
}


function runMigration()
{
    exec('php migrations/Migrate.php');
    echo "Migration triggered \n";
}

function runMigrationWithReverse($steps)
{
    if ($steps===null){
        $x=exec('php migrations/Migrate.php reverse');
        echo $x;
        echo "Migration with reverse (all steps) triggered \n";
    }else{
        $steps = intval($steps);
        exec('php migrations/Migrate.php reverse ' . $steps);
        echo "Migration with reverse " . $steps . " triggered \n";
    }
}

function test2CheckIfTableExists($tableName)
{
    try {
        $parseSchema = new \Parse\ParseSchema($tableName);
        $parseSchema = $parseSchema->get();
        echo "Class " . $tableName . " exists, fields: ";
        echo getFields($parseSchema['fields']);
        echo "\n";
    } catch (Exception $e) {
        if ($e->getMessage() == 'Class TestUser does not exist.') {
            echo "Class TestUser does not exist, terminating tests";
            die();
        }
    }
}

function getFields($array)
{
    $return = [];
    foreach ($array as $k => $v) {
        $return[] = $k . ' [' . $v['type'] . ']';
    }
    return implode(', ', $return);
}

function copyFirstScript($demoScriptsDirectory, $scriptsDirectory)
{
    copy($demoScriptsDirectory . '/20170404_1735__initial_migrate.php', $scriptsDirectory . '/20170404_1735__initial_migrate.php');
    echo "Preparing Test 2 - first migration script is copied to migration folder \n";
}

function test1CheckIfTableDoesNotExists($table)
{
    try {
        $parseSchema = new \Parse\ParseSchema($table);
        $parseSchema->get();
        $tableExists = true;
    } catch (Exception $e) {
        if ($e->getMessage() == 'Class '.$table.' does not exist.') {
            echo "Class '.$table.' does not exist \n";
        }
        $tableExists = false;
    }

    if ($tableExists) {
        echo 'ERROR, class '.$table.' exists, terminating tests';
        die();
    }
}


function createBackupDirectory($dirPath)
{
    mkdir($dirPath);
}

function removeDirectoryWithFiles($dirPath)
{
    $d = dir($dirPath);
    while (false !== ($entry = $d->read())) {
        if ($entry != '.' && $entry != '..') {
            unlink($dirPath . '/' . $entry);
        }
    }
    $d->close();

    rmdir($dirPath);
}

function moveFilesFromDirectoryToDirectory($fromPath, $toPath)
{
    $d = dir($fromPath);
    while (false !== ($entry = $d->read())) {
        if ($entry != '.' && $entry != '..') {
            copy($fromPath . '/' . $entry, $toPath . '/' . $entry);
            unlink($fromPath . '/' . $entry);
        }
    }
    $d->close();

    echo "moved files from ".$fromPath." into ".$toPath." \n";
}

function moveFileFromDirectoryToDirectory($fromPath, $toPath)
{
    copy($fromPath, $toPath);
    unlink($fromPath);
}