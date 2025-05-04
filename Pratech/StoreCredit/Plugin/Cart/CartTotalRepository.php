<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Plugin\Cart;

use Magento\Quote\Api\Data\TotalsInterface;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

class CartTotalRepository
{
    /**
     * Cart Total Repository Constructor
     *
     * @param StoreCreditHelper $storeCredithelper
     */
    public function __construct(
        private StoreCreditHelper $storeCredithelper
    ) {
    }

    /**
     * After Get Method.
     *
     * @param \Magento\Quote\Model\Cart\CartTotalRepository $subject
     * @param TotalsInterface $quoteTotals
     * @param int $cartId
     * @return TotalsInterface
     */
    public function afterGet(
        \Magento\Quote\Model\Cart\CartTotalRepository $subject,
        TotalsInterface                               $quoteTotals,
        int                                           $cartId
    ): TotalsInterface {
        $extensionAttributes = $quoteTotals->getExtensionAttributes();
        $extensionAttributes->setCashbackAmount($this->storeCredithelper->getCashbackAmount($quoteTotals));
        $quoteTotals->setExtensionAttributes($extensionAttributes);
        return $quoteTotals;
    }
}
