<?php

namespace App\Background\Repositories;

use App\Background\Entities\Partners\Grab\Retailer;
use App\Background\Entities\RetailerPartner;
use App\Background\Entities\RetailerPartnerList;
use App\Background\Entities\RetailerPartnerShortInfo;
use App\Background\Entities\RetailerPartnerShortInfoList;
use App\Background\Services\GrabIntegrationService;
use Parse\ParseObject;
use Parse\ParseQuery;

class RetailerPartnerParseRepository extends ParseRepository implements RetailerPartnerRepositoryInterface
{
    public function getListByPartnerName(string $partnerName): RetailerPartnerList
    {
        $parseQuery = new ParseQuery('RetailerPartners');
        $parseQuery->equalTo('partner', $partnerName);
        $parseQuery->equalTo('isActive', true);
        $parseQuery->includeKey('retailer');
        $parseQuery->limit(1000000);
        $parseRetailerPartners = $parseQuery->find(false, true);

        $list = new RetailerPartnerList();

        foreach ($parseRetailerPartners as $parseObject) {

            $retailerId = $parseObject->get('retailer') !== null ? (string)$parseObject->get('retailer')->getObjectId() : null;

            $airportParseObject = getAirportByIataCode((string)$parseObject->get('airportIataCode'));

            $list->addItem(new RetailerPartner(
                $parseObject->getObjectId(),
                $retailerId,
                (string)$parseObject->get('partner'),
                (int)$parseObject->get('partnerId'),
                (string)$parseObject->get('airportIataCode'),
                (bool)$parseObject->get('isActive'),
                new \DateTimeZone($airportParseObject->get('airportTimezone')),
                (string)$parseObject->get('itemsDirectoryName'),
                (string)$parseObject->get('retailerUniqueId')
            ));
        }

        return $list;
    }


    public function getListByPartnerNameAndAirportIataCode(
        string $partnerName,
        string $airportIataCode
    ): RetailerPartnerList {
        $parseQuery = new ParseQuery('RetailerPartners');
        $parseQuery->equalTo('partner', $partnerName);
        $parseQuery->equalTo('airportIataCode', $airportIataCode);
        $parseQuery->equalTo('isActive', true);
        $parseQuery->includeKey('retailer');
        $parseQuery->limit(1000000);
        $parseRetailerPartners = $parseQuery->find(false, true);

        $list = new RetailerPartnerList();

        foreach ($parseRetailerPartners as $parseObject) {

            $retailerId = $parseObject->get('retailer') !== null ? (string)$parseObject->get('retailer')->getObjectId() : null;

            $airportParseObject = getAirportByIataCode((string)$parseObject->get('airportIataCode'));

            $list->addItem(new RetailerPartner(
                $parseObject->getObjectId(),
                $retailerId,
                (string)$parseObject->get('partner'),
                (int)$parseObject->get('partnerId'),
                (string)$parseObject->get('airportIataCode'),
                (bool)$parseObject->get('isActive'),
                new \DateTimeZone($airportParseObject->get('airportTimezone')),
                (string)$parseObject->get('itemsDirectoryName'),
                (string)$parseObject->get('retailerUniqueId')
            ));
        }

        return $list;
    }

    public function addNotExistingRetailerPartnerByShortInfoList(
        RetailerPartnerShortInfoList $retailerPartnerShortInfoList
    ) {
        /** @var RetailerPartnerShortInfo $retailerPartnerShortInfo */
        foreach ($retailerPartnerShortInfoList as $retailerPartnerShortInfo) {

            $parseQuery = new ParseQuery('RetailerPartners');
            $parseQuery->equalTo('partner', $retailerPartnerShortInfo->getPartnerName());
            $parseQuery->equalTo('partnerId', $retailerPartnerShortInfo->getPartnerId());
            $parseQuery->equalTo('airportIataCode', $retailerPartnerShortInfo->getAirportIataCode());
            $parseQuery->equalTo('isActive', true);
            $parseQuery->limit(1);
            $parseRetailerPartners = $parseQuery->find();
            if (count($parseRetailerPartners) == 0) {
                $parseObject = new ParseObject('RetailerPartners');
                $parseObject->set('partner', $retailerPartnerShortInfo->getPartnerName());
                $parseObject->set('partnerId', $retailerPartnerShortInfo->getPartnerId());
                $parseObject->set('isActive', true);
                $retailerUniqueId = '';
                if ($retailerPartnerShortInfo->getPartnerName() == GrabIntegrationService::PARTNER_NAME) {
                    $retailerUniqueId = Retailer::getUniqueId($retailerPartnerShortInfo->getPartnerId());
                }
                $parseObject->set('retailerUniqueId', $retailerUniqueId);
                $parseObject->set('airportIataCode', $retailerPartnerShortInfo->getAirportIataCode());
                $parseObject->save();
            }
        }
    }

    public function updateItemsDirectoryName(RetailerPartner $retailerPartner): RetailerPartner
    {
        $parseRetailerPartner = new ParseObject('RetailerPartners', $retailerPartner->getId());
        $parseRetailerPartner->set('itemsDirectoryName', $retailerPartner->getItemsDirectoryName());
        $parseRetailerPartner->save();

        return $retailerPartner;
    }
}
