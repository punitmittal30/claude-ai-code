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

namespace Pratech\Cart\Api;

/**
 * Store Credit Interface to expose api related to customer balance
 */
interface StoreCreditInterface
{
    /**
     * Apply Store Credit
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function apply(int $cartId);

    /**
     * Remove Store Credit
     *
     * @param int $cartId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function remove(int $cartId);
}
