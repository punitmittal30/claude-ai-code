<?php
/**
 * Pratech_ThirdPartyIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ThirdPartyIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ThirdPartyIntegration\Api;

interface ExternalCustomerInterface
{
    /**
     * Get customer info by email.
     *
     * @param string $email
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function getCustomerByEmail(string $email): array;

    /**
     * Get customer info by mobile number.
     *
     * @param string $mobileNumber
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function getCustomerByMobileNumber(string $mobileNumber): array;
}
