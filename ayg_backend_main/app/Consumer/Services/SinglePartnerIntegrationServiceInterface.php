<?php
namespace App\Consumer\Services;

use App\Consumer\Dto\PartnerIntegration\Cart;
use App\Consumer\Dto\PartnerIntegration\CartTotals;
use App\Consumer\Dto\PartnerIntegration\EmployeeDiscount;

interface SinglePartnerIntegrationServiceInterface
{
    public function validateCart(Cart $cart);

    public function getCartTotals(Cart $cart, ?EmployeeDiscount $employeeDiscount): CartTotals;

    public function submitOrder(Cart $cart, CartTotals $cartTotals);

    public function submitOrderAsGuest(Cart $cart, CartTotals $cartTotals, ?EmployeeDiscount $employeeDiscountPromotionJson);

    public function getEmployeeDiscount(Cart $cart):EmployeeDiscount;

    public function getPartnerName(): string;
}
