<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Helper;

use Magento\CustomerBalance\Api\BalanceManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Store Credit Helper Class
 */
class StoreCredit
{
    /**
     * Store Credit Helper Constructor
     *
     * @param BalanceManagementInterface $balanceManagement
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        private BalanceManagementInterface $balanceManagement,
        private CartRepositoryInterface    $cartRepository
    ) {
    }

    /**
     * Apply Store Credit
     *
     * @param int $cartId
     * @return bool
     */
    public function apply(int $cartId)
    {
        return $this->balanceManagement->apply($cartId);
    }

    /**
     * Remove Store Credit
     *
     * @param int $cartId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function remove(int $cartId)
    {
        $quote = $this->cartRepository->get($cartId);
        $quote->setUseCustomerBalance(false);
        $quote->collectTotals();
        $quote->save();
        return true;
    }
}
