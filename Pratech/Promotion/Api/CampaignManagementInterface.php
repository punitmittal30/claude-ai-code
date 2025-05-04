<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Api;

interface CampaignManagementInterface
{
    /**
     * Credit Store Credit To Eligible Customer.
     *
     * @param string $promoCode
     * @param int $customerId
     * @param string $campaignType
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function creditBalance(string $promoCode, int $customerId, string $campaignType): array;
}
