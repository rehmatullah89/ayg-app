<?php

namespace App\Consumer\Services;


use App\Consumer\Dto\PartnerIntegration\Cart;
use App\Consumer\Dto\PartnerIntegration\CartTotals;
use App\Consumer\Dto\PartnerIntegration\EmployeeDiscount;
use App\Consumer\Entities\Partners\Grab\Promotion;
use App\Consumer\Entities\Partners\Grab\Retailer;
use App\Consumer\Entities\Partners\Grab\RetailerItemList;
use App\Consumer\Exceptions\Partners\OrderCanNotBeSaved;
use App\Consumer\Exceptions\Partners\PromotionIsNotValidException;
use App\Consumer\Helpers\PartnerIntegration\Grab\CartHelper;
use GuzzleHttp\Client;

class GrabIntegrationService extends Service implements SinglePartnerIntegrationServiceInterface
{
    const SHOPPING_CART_TAX_FEE = 'Cursus/Cursus_PartnerDirect_GetShoppingCartTaxFee';

    const RETAILER_INFO = 'Cursus/Cursus_PartnerDirect_GetStoreInventory';

    const VALIDATE_PROMOTION = 'Cursus/Cursus_ValidatePromotion';

    const SAVE_ORDER = 'Cursus/Cursus_PartnerDirect_SaveOrderV3';

    const SAVE_ORDER_AS_GUEST = 'Cursus/Cursus_PartnerDirect_SaveGuestCheckoutOrderV3';

    const RETAILER_IMAGE_URL_PREFIX = 'https://grabmobilewebtop.com/cursusmenuimages/';

    const RETAILER_ITEM_IMAGE_URL_PREFIX = 'https://grabmobilewebtop.com/cursusmenuimages/';

    const PARTNER_NAME = 'grab';

    const PARTNER_PREFIX = 'grab_';

    const EMPLOYEE_CODE = 'AIREMPLOYEE';

    /**
     * @var Client
     */
    private $guzzleClient;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $secretKey;
    /**
     * @var string
     */
    private $mainApiUrl;

    public function __construct(
        string $email,
        string $mainApiUrl,
        string $secretKey,
        Client $guzzleClient
    ) {
        $this->email = $email;
        $this->secretKey = $secretKey;
        $this->guzzleClient = $guzzleClient;
        $this->mainApiUrl = $mainApiUrl;
    }

    public function validateCart(Cart $cart)
    {
    }

    public function getPartnerName(): string
    {
        return self::PARTNER_NAME;
    }

    public function submitOrder(
        Cart $cart,
        CartTotals $cartTotals
    ) {
        $json = $this->callGetEndpoint(self::RETAILER_INFO, [
            'storeWaypointID' => $cart->getPartnerRetailerId()
        ]);
        $retailer = Retailer::createFromGrabRetailerInfoJson($json, $cart->getDateTimeZone());
        $itemList = RetailerItemList::createFromGrabRetailerInfoJson($json, $cart->getDateTimeZone());

        $postInput = CartHelper::getSaveOrderFromCart($cart, $this->secretKey, $itemList, $retailer, $cartTotals,
            false);

        logResponse("SERVY SAVE ORDER INPUT", false, true);
        logResponse(json_encode($postInput), false);

        $json = $this->callRawPostEndpoint(self::SAVE_ORDER, $postInput);
        logResponse("SERVY SAVE ORDER OUTPUT", false, true);
        logResponse($json, false);

        $decoded = json_decode($json, true);

        if (empty($decoded['orderID'])) {
            throw new OrderCanNotBeSaved(
                'Grab/Servy order can not be saved',
                0,
                null,
                json_encode($postInput),
                $json,
                self::SAVE_ORDER
            );
        }

        return $decoded;
    }

    public function getEmployeeDiscount(Cart $cart): EmployeeDiscount
    {
        $json = $this->callGetEndpoint(self::VALIDATE_PROMOTION, [
            'promotionCode' => self::EMPLOYEE_CODE,
            'storeWaypointID' => $cart->getPartnerRetailerId(),
            'isAppliedToAccount' => 'false'
        ]);

        try {
            $promotion = Promotion::createFromValidatePromotionJson($json);


            return new EmployeeDiscount(
                true,
                $promotion->isPercentage(),
                $promotion->getPercentage(),
                $promotion
            );

        } catch (PromotionIsNotValidException $exception) {
            return new EmployeeDiscount(false, null, null, null);
        }
    }


    public function submitOrderAsGuest(
        Cart $cart,
        CartTotals $cartTotals,
        ?EmployeeDiscount $employeeDiscount
    ) {
        $json = $this->callGetEndpoint(self::RETAILER_INFO, [
            'storeWaypointID' => $cart->getPartnerRetailerId()
        ]);
        $retailer = Retailer::createFromGrabRetailerInfoJson($json, $cart->getDateTimeZone());
        $itemList = RetailerItemList::createFromGrabRetailerInfoJson($json, $cart->getDateTimeZone());

        $postInput = CartHelper::getSaveOrderFromCart($cart, $this->secretKey, $itemList, $retailer, $cartTotals, true, $employeeDiscount);

        logResponse("SERVY SAVE AS GUEST ORDER INPUT", false, true);
        logResponse(json_encode($postInput), false);


        $json = $this->callRawPostEndpoint(self::SAVE_ORDER_AS_GUEST, $postInput);
        logResponse("SERVY SAVE AS GUEST ORDER OUTPUT", false, true);
        logResponse($json, false);

        $decoded = json_decode($json, true);

        if (empty($decoded['orderID'])) {
            throw new OrderCanNotBeSaved(
                'Grab/Servy can not be saved',
                0,
                null,
                json_encode($postInput),
                $json,
                self::SAVE_ORDER
            );
        }

        return $decoded;
    }

    public function getCartTotals(
        Cart $cart,
        ? EmployeeDiscount $employeeDiscount
    ): CartTotals {
        $json = $this->callGetEndpoint(self::RETAILER_INFO, [
            'storeWaypointID' => $cart->getPartnerRetailerId()
        ]);
        $itemList = RetailerItemList::createFromGrabRetailerInfoJson($json, $cart->getDateTimeZone());

        $json = $this->callRawPostEndpoint(self::SHOPPING_CART_TAX_FEE,
            CartHelper::getCartTaxFeeInputFromCart($cart, $this->secretKey, $itemList, $employeeDiscount)
        );
        $response = json_decode($json, true);

        return new CartTotals(
            intval(round($response['subTotal'] * 100)),
            intval(round($response['taxes'] * 100)),
            intval(round($response['orderTotal'] * 100))
        );
    }

    private
    function callRawPostEndpoint(
        string $path,
        \stdClass $input
    ): string {

        $url = $this->mainApiUrl . $path;
        $response = $this->guzzleClient->post($url,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body' => json_encode($input)
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('GRAB problem with url ' . $this->mainApiUrl . $path);
        }

        return (string)$response->getBody();
    }


    private
    function callGetEndpoint(
        string $path,
        array $additionalParams
    ): string {
        $url = $this->mainApiUrl . $path . '?email=' . $this->email . '&kobp=' . $this->secretKey;

        foreach ($additionalParams as $key => $value) {
            $url = $url . '&' . $key . '=' . $value;
        }

        $response = $this->guzzleClient->get($url);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('GRAB problem with url ' . $this->mainApiUrl . $path);
        }

        return (string)$response->getBody();
    }
}
