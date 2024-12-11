<?php
namespace App\Consumer\Responses;

use App\Consumer\Entities\SessionDevice;
use App\Consumer\Entities\SessionDeviceList;

/**
 * Class UserSessionDeviceResponse
 */
class UserSessionDeviceResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var SessionDeviceList
     */
    private $data;

    public function __construct(SessionDeviceList $sessionDeviceList)
    {
        $this->data = $sessionDeviceList;
    }

    public static function createFromSessionDeviceList(SessionDeviceList $sessionDeviceList)
    {
        return new UserSessionDeviceResponse($sessionDeviceList);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $newFlatList = [];

        foreach ($this->data as $item) {
            /**
             * @var $item SessionDevice
             */
            $newFlatList[] = [
                'checkinTimestamp' => $item->getCheckinTimestamp(),
                'IPAddress' => $item->getIPAddress(),
                'deviceType' => $item->getUserDevice()->getDeviceType(),
                'deviceId' => $item->getUserDevice()->getDeviceId(),
                'appVersion' => $item->getUserDevice()->getAppVersion(),
                'deviceModel' => $item->getUserDevice()->getDeviceModel(),
                'deviceOS' => $item->getUserDevice()->getDeviceOS()
            ];
        }

        return $newFlatList;
    }
}
