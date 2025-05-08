<?php
/**
 * Pratech_Auth
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Auth
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Auth\Api;

/**
 * Interface AuthenticationManagementInterface
 *
 * @api
 */
interface AuthenticationManagementInterface
{
    /**
     * Create a customer account.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\InputException If bad input is provided
     * @throws \Magento\Framework\Exception\State\InputMismatchException If the provided email is already used
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function register(\Magento\Customer\Api\Data\CustomerInterface $customer, $password = null): array;
}
