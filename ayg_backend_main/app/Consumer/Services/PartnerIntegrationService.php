<?php

namespace App\Consumer\Services;


use App\Consumer\Dto\PartnerIntegration\Cart;
use App\Consumer\Dto\PartnerIntegration\CartTotals;
use App\Consumer\Dto\PartnerIntegration\EmployeeDiscount;
use App\Consumer\Entities\RetailerPartner;
use App\Consumer\Repositories\RetailerPartnerRepositoryInterface;

class PartnerIntegrationService extends Service
{
    /**
     * @var SinglePartnerIntegrationServiceInterface
     */
    private $integrationService;
    /**
     * @var RetailerPartnerRepositoryInterface
     */
    private $retailerPartnerRepository;

    public function __construct(
        SinglePartnerIntegrationServiceInterface $integrationService,
        RetailerPartnerRepositoryInterface $retailerPartnerRepository
    ) {
        $this->integrationService = $integrationService;
        $this->retailerPartnerRepository = $retailerPartnerRepository;
    }

    public function getCartTotals(Cart $cart, ? EmployeeDiscount $employeeDiscount): CartTotals
    {
        return $this->integrationService->getCartTotals($cart, $employeeDiscount);
    }

    public function submitOrder(Cart $cart, CartTotals $cartTotals)
    {
        return $this->integrationService->submitOrder($cart, $cartTotals);
    }

    public function submitOrderAsGuest(Cart $cart, CartTotals $cartTotals, ?EmployeeDiscount $employeeDiscountPromotion)
    {
        return $this->integrationService->submitOrderAsGuest($cart, $cartTotals, $employeeDiscountPromotion);
    }

    public function getEmployeeDiscount(Cart $cart):EmployeeDiscount
    {
        return $this->integrationService->getEmployeeDiscount($cart);
    }

    public function getPartnerIdByRetailerUniqueId($retailerUniqueId):?RetailerPartner
    {
        return $this->retailerPartnerRepository->getRetailerPartnerByRetailerUniqueId($retailerUniqueId);
    }

    public function getPartnerName()
    {
        return $this->integrationService->getPartnerName();
    }
}
