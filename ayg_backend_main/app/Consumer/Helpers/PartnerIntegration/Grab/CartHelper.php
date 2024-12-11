<?php
namespace App\Consumer\Helpers\PartnerIntegration\Grab;

use App\Consumer\Dto\PartnerIntegration\Cart;
use App\Consumer\Dto\PartnerIntegration\CartItem;
use App\Consumer\Dto\PartnerIntegration\CartTotals;
use App\Consumer\Dto\PartnerIntegration\EmployeeDiscount;
use App\Consumer\Entities\Partners\Grab\Retailer;
use App\Consumer\Entities\Partners\Grab\RetailerItem;
use App\Consumer\Entities\Partners\Grab\RetailerItemChoice;
use App\Consumer\Entities\Partners\Grab\RetailerItemChoiceGroup;
use App\Consumer\Entities\Partners\Grab\RetailerItemInventorySub;
use App\Consumer\Entities\Partners\Grab\RetailerItemList;
use App\Consumer\Entities\Partners\Grab\RetailerItemOption;
use App\Consumer\Entities\Partners\Grab\RetailerItemOptionGroup;
use App\Consumer\Services\GrabIntegrationService;

class CartHelper
{
    const UNIQUE_ID_PREFIX = 'grab_';

    private static function getSub($itemId, $retailerItemList): ?RetailerItemInventorySub
    {
        $subId = self::getSubId($itemId);
        /** @var RetailerItem $item */
        foreach ($retailerItemList as $item) {
            /** @var RetailerItemInventorySub $retailerItemInventorySub */
            foreach ($item->getRetailerItemInventorySubList() as $retailerItemInventorySub) {
                if ($retailerItemInventorySub->getInventoryItemSubID() == $subId) {
                    return $retailerItemInventorySub;
                }
            }
        }
        return null;
    }

    private static function getSubId($itemId): string
    {
        $itemId = explode('_', $itemId);
        if (isset($itemId[count($itemId) - 1])) {
            return $itemId[count($itemId) - 1];
        }

        return '';
    }

    public static function getSaveOrderFromCart(
        Cart $cart,
        string $secretKey,
        RetailerItemList $retailerItemList,
        Retailer $retailer,
        CartTotals $cartTotals,
        bool $withCCInfo,
        ?EmployeeDiscount $employeeDiscount
    ) {

        $guestCustomerDetails = new \StdClass();
        $guestCustomerDetails->firstName = $cart->getCartUserDetails()->getFirstName();
        $guestCustomerDetails->lastName = $cart->getCartUserDetails()->getLastName();
        $guestCustomerDetails->enableTextNotifications = 0;
        $guestCustomerDetails->mobileNumberCountry = "";
        $guestCustomerDetails->mobileNumber = "";

        $ccInfo = new \StdClass();
        $ccInfo->BraintreeV2CardType = "";
        $ccInfo->cardHolder = "";
        $ccInfo->zipCode = "";
        $ccInfo->bGrabIsMerchantOfRecord = false;
        $ccInfo->partnerCreditCardLastFour = "";
        $ccInfo->partnerCreditCardName = "";
        $ccInfo->partnerCreditCardAuth = "";

        if ($withCCInfo) {
            $ccInfo = new \StdClass();
            $ccInfo->BraintreeV2CardType = "";
            $ccInfo->cvv = "";
            $ccInfo->cardHolder = "";
            $ccInfo->cardNumber = "";
            $ccInfo->zipCode = "";
            $ccInfo->expiration = "";
            $ccInfo->bGrabIsMerchantOfRecord = false;
            $ccInfo->partnerCreditCardLastFour = "";
            $ccInfo->partnerCreditCardName = "";
            $ccInfo->partnerCreditCardAuth = "";

            /*
            $ccInfo = new \StdClass();
            $ccInfo->cardNumber = "4111-1111-1111-1111";
            $ccInfo->zipCode = "77441";
            $ccInfo->cvv = "441";
            $ccInfo->expiration = "11/18";
            $ccInfo->cardHolder = "Test Card";
            */

        }

        $order = new \StdClass();
        $order->guestCustomerDetails = $guestCustomerDetails;
        $order->cartToken = "U4MBpW0JeEaXdGOQPuKyvQ=="; // @todo check if can be empty
        $order->ccInfo = $ccInfo;
        $order->kobp = $secretKey;
        $order->storeWaypointDescription = $retailer->getStoreWaypointDescription();
        $order->bHoldOrder = false;

        // we use cart, since it does not have discounted value, cartTotals has
        $order->totalCost = number_format(round(($cart->getSubTotal()+$cartTotals->getTax()) / 100, 2), 2, '.', '');
        $order->deliveryNotesForOrder = "";
        $order->airportIdent = $retailer->getAirportIdent();
        $order->originTimeLocal = (new \DateTime('now', $cart->getDateTimeZone()))->format('c');
        $order->storeID = $retailer->getStoreID();
        $order->deliveryHandlingTimeMessage = "";
        $order->storeWaypointID = $retailer->getStoreWaypointID();
        $order->arrCart = self::getItems($cart, $retailerItemList, $employeeDiscount, false);
        $order->storeName = $retailer->getStoreName();
        $order->bCreditCardTransaction = true;
        $order->email = "tech+servyorder@atyourgate.com";
        $order->deliverySelected = $cart->getIsDelivery() === true ? true : false;
        $order->deliveryLocationForOrder = "";


        if ($employeeDiscount !== null) {
            $employeeDiscountPromotion = json_decode(json_encode($employeeDiscount->getAdditionalData()), true);

            $promotion = new \StdClass();
            $promotion->firstOrderOnly = $employeeDiscountPromotion['firstOrderOnly'];
            $promotion->fundingType = $employeeDiscountPromotion['fundingType'];
            $promotion->isEmployeeDiscountPromotion = $employeeDiscountPromotion['isEmployeeDiscountPromotion'];
            $promotion->maxPromotionValue = $employeeDiscountPromotion['maxPromotionValue'];
            $promotion->partnerCode = $employeeDiscountPromotion['partnerCode'];
            $promotion->promotionCode = $employeeDiscountPromotion['promotionCode'];
            $promotion->promotionConfirmMsg = $employeeDiscountPromotion['promotionConfirmMsg'];
            $promotion->promotionConfirmMsgImage = $employeeDiscountPromotion['promotionConfirmMsgImage'];
            $promotion->promotionConfirmMsgTitle = $employeeDiscountPromotion['promotionConfirmMsgTitle'];
            $promotion->promotionDescription = $employeeDiscountPromotion['promotionDescription'];
            $promotion->promotionEmail = $employeeDiscountPromotion['promotionEmail'];
            $promotion->promotionEndDate = $employeeDiscountPromotion['promotionEndDate'];
            $promotion->promotionID = $employeeDiscountPromotion['promotionID'];
            $promotion->promotionMaxValue = $employeeDiscountPromotion['promotionMaxValue'];
            $promotion->promotionOwner = $employeeDiscountPromotion['promotionOwner'];
            $promotion->promotionStartDate = $employeeDiscountPromotion['promotionStartDate'];
            $promotion->promotionTypeCode = $employeeDiscountPromotion['promotionTypeCode'];
            $promotion->promotionTypeDescription = $employeeDiscountPromotion['promotionTypeDescription'];
            $promotion->promotionValue = $employeeDiscountPromotion['promotionValue'];
            $promotion->registrationDate = $employeeDiscountPromotion['registrationDate'];
            $promotion->storeWaypointID = $employeeDiscountPromotion['storeWaypointID'];

            $order->promotion = $promotion;
        }


        return $order;
    }

    public static function getCartTaxFeeInputFromCart(
        Cart $cart,
        string $secretKey,
        RetailerItemList $retailerItemList,
        ? EmployeeDiscount $employeeDiscount
    ) {
        $cartSubtotal = $cart->getSubTotal();

        //if ($employeeDiscount !== null && $employeeDiscount->isIsApplicable() && $employeeDiscount->isPercentage()) {
        //    $cartSubtotal = $cartSubtotal - $cartSubtotal * $employeeDiscount->getDiscountPercentage() / 100;
        //}

        $input = new \StdClass();
        $input->storeWaypointID = $cart->getPartnerRetailerId();
        $input->storeID = "";
        $input->bCreditCardTransaction = true;
        $input->kobp = $secretKey;
        $input->arrCart = self::getItems($cart, $retailerItemList, $employeeDiscount, true);
        $input->totalCost = round($cartSubtotal / 100, 2);
        $input->email = "";
        $input->storeName = "";
        $input->storeWaypointDescription = "";
        $input->airportIdent = "";
        $input->bHoldOrder = false;

        if ($employeeDiscount!==null){
            // added promotion object
            $input->promotion = $employeeDiscount->getAdditionalData();
        }

        logResponse(json_encode('TAX CALCULATION'),false);
        logResponse(json_encode($input),false);

        return $input;
    }

    private static function getItems(
        Cart $cart,
        RetailerItemList $retailerItemList,
        ? EmployeeDiscount $employeeDiscount,
        bool $useDiscountedCost
    ) {
        // items
        $items = [];
        /** @var CartItem $cartItem */
        foreach ($cart->getCartItemList() as $cartItemKey => $cartItem) {
            $fullItemId = $cartItem->getItemId();
            $itemId = str_replace(GrabIntegrationService::PARTNER_PREFIX, '', $fullItemId);
            $itemId = explode('_', $itemId);
            $itemId = $itemId[0];
            $selectedSubId = (string)self::getSubId($fullItemId);
            //$selectedSubId = 1000;

            $itemInventoryMainOptionChoice = new \StdClass();
            $itemInventoryMainOptionChoice->options = self::getItemOptions($cartItem, $retailerItemList, $itemId,
                $selectedSubId);
            $itemInventoryMainOptionChoice->choices = self::getItemChoices($cartItem, $retailerItemList, $itemId,
                $selectedSubId);

            $item = new \StdClass();
            $item->inventoryItemSubs = self::getItemSubs($cartItem, $retailerItemList, $selectedSubId, (string)$itemId,
                $employeeDiscount, $useDiscountedCost);
            $item->quantity = $cartItem->getItemQuantity();
            $item->specialNotes = $cartItem->getItemComment();
            $item->inventoryOrder = "1"; // @todo - check what is that
            $item->inventoryMainOptionChoice = $itemInventoryMainOptionChoice;
            $item->inventoryItemID = $itemId;
            $item->inventoryItemName = $cartItem->getItemName(); // $todo check if needed

            $items[] = $item;
        }
        return $items;
    }

    private static function getItemSubs(
        CartItem $cartItem,
        RetailerItemList $retailerItemList,
        string $subId,
        string $itemId,
        ? EmployeeDiscount $employeeDiscount,
        bool $useDiscountedCost
    ): array {
        $subs = [];
        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            if ($retailerItem->getInventoryItemID() != $itemId) {
                continue;
            }

            /** @var RetailerItemInventorySub $retailerItemInventorySub */
            foreach ($retailerItem->getRetailerItemInventorySubList() as $retailerItemInventorySub) {

                $itemInventoryItemSub = new \StdClass();
                $itemInventoryItemSub->inventoryItemSubID = $retailerItemInventorySub->getInventoryItemSubID(); // $todo check if needed
                $itemInventoryItemSub->inventoryItemSubName = $retailerItemInventorySub->getInventoryItemSubName();

                if ($subId == $retailerItemInventorySub->getInventoryItemSubID()) {
                    $itemInventoryItemSub->selected = true;

                    $cost = $cartItem->getItemPrice();
                    //if ($useDiscountedCost && $employeeDiscount !== null && $employeeDiscount->isIsApplicable() && $employeeDiscount->isPercentage()) {
                    //    $cost = $cost - round(($cost * $employeeDiscount->getDiscountPercentage() / 100));
                    //}

                    $itemInventoryItemSub->cost = number_format(round($cost / 100, 2), 2, '.',
                        '');
                } else {
                    $itemInventoryItemSub->selected = false;
                    $itemInventoryItemSub->cost = $retailerItemInventorySub->getCost();
                }

                $subs[] = $itemInventoryItemSub;
            }

        }
        return $subs;
    }

    private static function getItemOptions(
        CartItem $cartItem,
        RetailerItemList $retailerItemList,
        string $itemId,
        string $selectedSubId
    ): array {
        $options = [];
        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            if ($itemId == $retailerItem->getInventoryItemID()) {
                /** @var RetailerItemOptionGroup $optionGroup */
                foreach ($retailerItem->getRetailerItemOptionGroupList() as $optionGroup) {
                    $stdObjOption = new \StdClass();

                    $subOptionList = [];
                    /** @var RetailerItemOption $retailerItemOption */
                    foreach ($optionGroup->getRetailerItemOptionList() as $retailerItemOption) {
                        $subOption = new \StdClass();
                        $subOption->optionDescription = $retailerItemOption->getOptionDescription();
                        $subOption->optionCost = $retailerItemOption->getOptionCost();
                        $subOption->optionGroupName = $optionGroup->getOptionGroupName();
                        $subOption->selected = self::isOptionOrChoiceSelected($cartItem,
                            self::getOptionId($itemId, $optionGroup->getOptionID(),
                                $retailerItemOption->getUniqueId(), $selectedSubId));
                        $subOption->optionID = $optionGroup->getOptionID();
                        $subOption->optionOrder = $retailerItemOption->getOptionOrder();
                        $subOptionList[] = $subOption;
                    }

                    $stdObjOption->inventoryOptions = $subOptionList;
                    $stdObjOption->optionGroupName = $optionGroup->getOptionGroupName();
                    $stdObjOption->optionSelection = $optionGroup->getOptionSelection();

                    $options[] = $stdObjOption;

                }
                break;
            }
        }
        return $options;
    }

    private static function getItemChoices(
        CartItem $cartItem,
        RetailerItemList $retailerItemList,
        string $itemId,
        string $selectedSubId
    ): array {
        $choices = [];
        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            if ($itemId == $retailerItem->getInventoryItemID()) {
                /** @var RetailerItemChoiceGroup $choice */
                foreach ($retailerItem->getRetailerItemChoiceGroupList() as $choice) {
                    $stdObjChoice = new \StdClass();

                    $subChoiceList = [];
                    /** @var RetailerItemChoice $retailerItemChoice */
                    foreach ($choice->getRetailerItemChoiceList() as $retailerItemChoice) {
                        $subChoice = new \StdClass();
                        $subChoice->choiceDescription = $retailerItemChoice->getChoiceDescription();
                        $subChoice->choiceCost = $retailerItemChoice->getChoiceCost();
                        $subChoice->choiceGroupName = $choice->getChoiceGroupName();
                        $subChoice->selected = self::isOptionOrChoiceSelected($cartItem,
                            self::getChoiceId($itemId, $choice->getChoiceID(),
                                $retailerItemChoice->getUniqueId(), $selectedSubId));
                        $subChoice->choiceID = $choice->getChoiceID();
                        $subChoice->choiceOrder = $retailerItemChoice->getChoiceOrder();
                        $subChoiceList[] = $subChoice;
                    }

                    $stdObjChoice->inventoryChoices = $subChoiceList;
                    $stdObjChoice->choiceGroupName = $choice->getChoiceGroupName();
                    $stdObjChoice->choiceSelection = $choice->getChoiceSelection();

                    $choices[] = $stdObjChoice;

                }
                break;
            }
        }
        return $choices;
    }

    private static function isOptionOrChoiceSelected(CartItem $cartItem, $id)
    {
        foreach ($cartItem->getCartItemOptionList() as $cartItemOption) {

            if ($cartItemOption->getOptionId() == $id) {
                return true;
            }
        }
        return false;
    }

    private static function getOptionId($itemId, $optionGroupId, $optionUniqueId, $selectedSubId)
    {
        return self::UNIQUE_ID_PREFIX . $itemId . '_' . $selectedSubId . '_mod_from_option_' . $optionGroupId . '_' . $optionUniqueId;
    }

    private static function getChoiceId($itemId, $choiceGroupId, $choiceUniqueId, $selectedSubId)
    {
        return self::UNIQUE_ID_PREFIX . $itemId . '_' . $selectedSubId . '_mod_from_choice_' . $choiceGroupId . '_' . $choiceUniqueId;
    }
}

