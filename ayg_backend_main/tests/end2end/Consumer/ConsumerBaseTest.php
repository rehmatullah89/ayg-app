<?php
/**
 * @todo use composer auto loader
 */
use Parse\ParseObject;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseUser;

require_once __DIR__ . '/../../../' . 'putenv.php';
require_once __DIR__ . '/../../../' . 'vendor/autoload.php';
require_once __DIR__ . '/' . 'Response.php';
require_once __DIR__ . '/' . 'ObjectsStackItem.php';

date_default_timezone_set('America/New_York');
ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 32768);
ParseClient::setServerURL(getenv('env_ParseServerURL'), '/parse');
ParseClient::initialize(getenv('env_ParseApplicationId'), getenv('env_ParseRestAPIKey'), getenv('env_ParseMasterKey'));

/**
 * Class ConsumerBaseTest
 * this class provides functionality like get, post to test endpoints
 */
abstract class ConsumerBaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectsStackItem[]
     *
     * values that are added during the test (or by tested endpoint) and need to be deleted after test is done
     */
    protected $objectsStack = [];

    /**
     * @var ParseUser
     *
     * current logged user
     */
    protected $currentUser = null;

    /**
     * @var null
     *
     * logged user session
     */
    protected $currentSessionToken = null;

    /**
     * @var null
     *
     * bool can be set to delete entries that can not be deleted without Master Key
     * USE VERY CAREFULLY
     */
    protected $forceMasterDelete = null;

    /**
     * @return void
     *
     * method automatically run when tests are finished (PHPUnit functionality)
     */
    protected function tearDown()
    {
        $this->deleteAllAddedObjectsAndLogout();
        parent::tearDown();
    }

    /**
     * @param $path
     * @param $fields
     * @return Response
     *
     * this method emulates making POST on a given url with a given fields
     */
    public static function post($path, $fields)
    {
        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_POST, count_like_php5($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $response = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        //close connection
        curl_close($ch);

        return new Response($response, $httpStatus);
    }

    /**
     * @param $path
     * @return Response
     *
     * this method emulates making GET on a given url
     */
    public static function get($path)
    {
        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //set the url
        curl_setopt($ch, CURLOPT_URL, $path);

        //execute post
        $response = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        // debugging if needed
        // var_dump(curl_errno($ch));
        // var_dump(curl_error($ch));

        //close connection
        curl_close($ch);

        return new Response($response, $httpStatus);
    }

    /**
     * @param $prefix
     * @param $sessionToken
     * @param $postfix
     * @return string
     *
     * gets full path (adds url, adds prefix, generates session part, adds postfix)
     */
    public static function generatePath($prefix, $sessionToken, $postfix)
    {
        $epoch = microtime(true) * 1000;
        $keySalts = explode(",", getenv('env_AppRestAPIKeySalt'));
        $apiKey = md5($epoch . $keySalts[0]);

        //$apiKey = '_';
        if ($postfix != '') {
            $postfix = '/' . $postfix;
        }

        $sessionToken = $sessionToken . '-c';

        return getenv('env_EnvironmentDevTestHost') . '/' . $prefix . "/a/" . $apiKey . "/e/" . $epoch . "/u/" . $sessionToken . $postfix;
    }

    /**
     * @param $prefix
     * @param $sessionToken
     * @param $postfix
     * @return string
     *
     * gets full path (adds url, adds prefix, generates session part, adds postfix)
     */
    public static function generatePathForWebEndpoints($prefix, $sessionToken, $postfix)
    {
        $epoch = microtime(true) * 1000;
        $keySalts = explode(",", getenv('env_WebRestAPIKeySalt'));
        $apiKey = md5($epoch . $keySalts[0]);

        //$apiKey = '_';
        if ($postfix != '') {
            $postfix = '/' . $postfix;
        }

        $sessionToken = $sessionToken . '-c';

        return getenv('env_EnvironmentDevTestHost') . '/' . $prefix . "/a/" . $apiKey . "/e/" . $epoch . "/u/" . $sessionToken . $postfix;
    }


    /**
     * gets full path
     * @param $path
     * @return string
     *
     * generates path where there is no session
     */
    public static function generatePathWithoutSession($path)
    {
        $epoch = microtime(true) * 1000;
        $keySalts = explode(",", getenv('env_AppRestAPIKeySalt'));
        $apiKey = md5($epoch . $keySalts[0]);


        return getenv('env_EnvironmentDevTestHost') . '/' . $path;
    }

    /**
     * @param $prefix
     * @param $userId
     * @param $postfix
     * @return string
     *
     * gets full path (adds url, adds prefix, generates session part, adds postfix)
     * (old way of passing user data)
     */
    public static function generatePathWithUserId($prefix, $userId, $postfix)
    {
        $epoch = microtime(true) * 1000;
        $keySalts = explode(",", getenv('env_AppRestAPIKeySalt'));
        $apiKey = md5($epoch . $keySalts[0]);

        //$apiKey = '_';
        if ($postfix != '') {
            $postfix = '/' . $postfix;
        }

        return getenv('env_EnvironmentDevTestHost') . '/' . $prefix . "/a/" . $apiKey . "/e/" . $epoch . "/u/" . $userId . $postfix;
    }

    /**
     * @param string $className
     * @param array $data
     * @return ParseObject
     *
     * adds new Parse object into the database
     */
    public function addParseObject($className, array $data)
    {
        $obj = new ParseObject($className);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $obj->setArray($key, $value);
            } else {
                $obj->set($key, $value);
            }
        }
        $obj->save();
        return $obj;
    }

    /**
     * @param string $className
     * @param string $objectId
     * @param array $data
     * @return ParseObject
     *
     * changes parse object
     */
    public function modifyParseObject($className, $objectId, array $data)
    {
        $obj = new ParseObject($className, $objectId);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $obj->setArray($key, $value);
            } else {
                $obj->set($key, $value);
            }
        }
        $obj->save();
        return $obj;
    }

    /**
     * @param string $className
     * @param array $data
     * @return ParseObject
     *
     * adds object to the Parse,
     * then put it onto the stack (self objectsStack) - so it can be deleted after tests are finished
     */
    public function addParseObjectAndPushObjectOnStack($className, array $data)
    {
        // add to the parse
        $obj = $this->addParseObject($className, $data);
        $objId = $obj->getObjectId();
        $this->pushOnObjectsStack(new ObjectsStackItem($objId, $className));
        return $obj;
    }

    /**
     * @param ObjectsStackItem $objectsStackItem
     *
     * then put ObjectsStackItem onto the stack (self objectsStack) - so it can be deleted after tests are finished
     */
    public function pushOnObjectsStack(ObjectsStackItem $objectsStackItem)
    {
        $this->objectsStack[] = $objectsStackItem;
    }

    /**
     * @return ObjectsStackItem|null
     *
     * remove last added ObjectsStackItem from the stack and return this object
     */
    public function popFromObjectsStack()
    {
        $objectsStackCount = count_like_php5($this->objectsStack);
        if ($objectsStackCount == 0) {

            return null;
        }
        $object = $this->objectsStack[$objectsStackCount - 1];
        unset($this->objectsStack[$objectsStackCount - 1]);


        return $object;
    }

    /**
     * @return void
     * pops all values from stack and deletes related Parse Entries
     */
    public function deleteAllAddedObjects()
    {
        // set yourself as logged user
        if ($this->currentSessionToken !== null) {
            ParseUser::become(substr($this->currentSessionToken, 0, -2));
        }


        while (1) {
            $object = $this->popFromObjectsStack();

            if ($object === null) {
                break;
            }

            try {
                $parseObject = new ParseObject($object->getClassName(), $object->getId());

                if ($this->forceMasterDelete === true) {
                    $parseObject->destroy(true);
                } else {
                    $parseObject->destroy();

                }
            } catch (Exception $e) {

                var_dump(
                    'Problem with with deleting:',
                    $object->getClassName(),
                    $object->getId(),
                    $e->getMessage());
            }
        }
    }

    /**
     * @return void
     *
     * logout current Parse user if logged - needed to login by sessionId in next test
     */
    public function logout()
    {
        // try to logout
        try {
            ParseUser::logOut();
        } catch (Exception $e) {
            //var_dump('could not logout');
        }
    }

    /**
     * @return void
     *
     * pops all values from stack and deletes related Parse Entries
     * logout current Parse user if logged - needed to login by sessionId in next test
     */
    public function deleteAllAddedObjectsAndLogout()
    {
        $this->deleteAllAddedObjects();
        $this->logout();
    }

    /**
     * @param array $data
     * @return ParseUser
     *
     * creates user and put him on the stack
     */
    public function createUser(array $data = [])
    {
        $email = 'ludwik.grochowina+mainusercreate' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $username = $email . '-c';

        $this->currentUser = new ParseUser();
        $this->currentUser->set("username", $username);
        $this->currentUser->set("email", $email);
        $this->currentUser->set("lastName", 'Customer app tests');
        $this->currentUser->set("password", md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->currentUser->set("isActive", true);
        $this->currentUser->set("hasConsumerAccess", true);
        $this->currentUser->set("isBetaActive", true);

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->currentUser->set($key, $value);
                if ($key == 'email') {
                    $username = $value . '-c';
                    $this->currentUser->set("username", $username);
                }
            }
        }

        $this->currentUser->signUp();

        $this->pushOnObjectsStack(
            new ObjectsStackItem(
                $this->currentUser->getObjectId(),
                '_User'
            )
        );

        // add SessionDevices

        $sessionDevice = new ParseObject('SessionDevices');
        $sessionDevice->set('sessionTokenRecall', $this->currentUser->getSessionToken());
        $sessionDevice->set('user', $this->currentUser);
        $sessionDevice->set('isActive', true);
        $sessionDevice->save();


        $this->pushOnObjectsStack(
            new ObjectsStackItem(
                $sessionDevice->getObjectId(),
                'SessionDevices'
            )
        );

        return $this->currentUser;
    }

    /**
     * @return array|ParseObject
     *
     * method to get first retailer from the database
     */
    public function parseGetFirstRetailer()
    {
        $query = new ParseQuery('Retailers');
        return $query->first();
    }

    /**
     * @param ParseUser $user
     * @return array|ParseObject
     *
     * method to get first Payment for a given user from the database
     */
    public function parseGetPaymentForUser(ParseUser $user)
    {
        $query = new ParseQuery('Payments');
        $query->equalTo('user', $user);
        return $query->first();
    }

    /**
     * @return array|ParseObject
     *
     * method to get retailer by id from the database
     */
    public function parseGetRetailerById($id)
    {
        return new ParseObject('Retailers', $id);
    }

    /**
     * @return array|ParseObject
     *
     * method to get all retailers from the database
     */
    public function parseGetAllRetailers()
    {
        $query = new ParseQuery('Retailers');
        return $query->find();
    }

    /**
     * @return string
     *
     * method to get first retailer's UniqueId from the database
     *
     */
    public function parseGetRetailerUniqueIdForFirstRetailerItem()
    {
        $query = new ParseQuery('RetailerItems');
        $retailerItems = $query->first();
        return $retailerItems->get('uniqueRetailerId');
    }

    /**
     * @return array|ParseObject
     *
     * method to get first retailer with POSConfig from the database
     */
    public function parseGetFirstRetailerWithPosConfig()
    {
        $query = new ParseQuery('RetailerPOSConfig');
        $retailerPOSConfig = $query->first();
        $retailerPOSConfig->fetch('retailer');
        return $retailerPOSConfig->get('retailer');
    }

    /**
     * @return array|ParseObject
     *
     * method to get first retailer with POSConfig from the database
     */
    public function getActiveRetailerItemWithExistingRetailer()
    {
        $query = new ParseQuery('RetailerItems');
        $query->equalTo('isActive', true);
        $items = $query->find();

        foreach ($items as $item) {
            $query = new ParseQuery('Retailers');
            $query->equalTo('uniqueId', $item->get('uniqueRetailerId'));
            $retailer = $query->first();
            if (!empty($retailer)) {
                return $retailer;
            }
        }
    }


    /**
     * @param string $uniqueId
     * @return array|ParseObject
     *
     * method to get retailer by uniqueId from the database
     */
    public function getRetailerByUniqueId($uniqueId)
    {
        $query = new ParseQuery('Retailers');
        $query->equalTo('uniqueId', $uniqueId);
        return $query->first();
    }

    /**
     * @param string $uniqueRetailerId
     * @return array|ParseObject
     *
     * gets first Item from retailer (by retailer UniqueId)
     */
    public function getFirstActiveRetailerItemByRetailerUniqueId($uniqueRetailerId)
    {
        $query = new ParseQuery('RetailerItems');
        $query->equalTo('uniqueRetailerId', $uniqueRetailerId);
        $query->equalTo('isActive', true);
        return $query->first();
    }


    /**
     * @return mixed
     *
     * gets UniqueId for first retailer with POSConfig value
     */
    protected function parseFindFirstRetailerWithRetailerPOSConfigAndGetUniqueId()
    {
        $query = new ParseQuery('RetailerPOSConfig');
        $retailerPOSConfigs = $query->find();
        foreach ($retailerPOSConfigs as $retailerPOSConfig) {
            $retailer = $retailerPOSConfig->get('retailer');
            $retailer->fetch();
            if ($retailer->get('isActive')) {
                return $retailer->get('uniqueId');
            }
        }
        return null;
    }

    /**
     * @param $path
     * @return mixed true on success or false on failure. However, if the CURLOPT_RETURNTRANSFER
     * option is set, it will return the result on success, false on failure. - from Curl Documentation
     *
     * helpfull for tripit functionality
     * gets full website, supports cookies
     */
    function getWebsite($path)
    {
        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //set the url
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'storage/cookie/tripitcookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'storage/cookie/tripitcookie.txt');

        //execute post
        $response = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        //close connection
        curl_close($ch);

        return $response;
    }


    /**
     * @param $path
     * @param $fields
     * @return mixed true on success or false on failure. However, if the CURLOPT_RETURNTRANSFER
     * option is set, it will return the result on success, false on failure. - from Curl Documentation
     *
     * post value to a given page, support cookies
     */
    function postWebsite($path, $fields)
    {
        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'storage/cookie/tripitcookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'storage/cookie/tripitcookie.txt');
        curl_setopt($ch, CURLOPT_POST, count_like_php5($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        //execute post
        $response = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        //close connection
        curl_close($ch);

        return $response;
    }
}