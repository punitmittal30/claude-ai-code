<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Api;

/**
 * Store Credit Interface to expose api related to customer balance
 */
interface StoreCreditInterface
{
    /**
     * Get Store Credit Transaction Data
     *
     * @param  int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreCreditTransaction(int $customerId): array;
}
