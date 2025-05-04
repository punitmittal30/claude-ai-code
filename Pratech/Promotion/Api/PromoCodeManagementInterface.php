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

interface PromoCodeManagementInterface
{
    /**
     * Generate promo code for a promotion
     *
     * @param \Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterface $promoCodeSpec
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate(\Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterface $promoCodeSpec);

    /**
     * Delete promo codes by code ids.
     *
     * @param int[] $ids
     * @param bool $ignoreInvalidCoupons
     * @return \Pratech\Promotion\Api\Data\PromoCodeMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByIds(array $ids, $ignoreInvalidCoupons = true);

    /**
     * Delete promo by promo codes.
     *
     * @param string[] $codes
     * @param bool $ignoreInvalidCoupons
     * @return \Pratech\Promotion\Api\Data\PromoCodeMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByCodes(array $codes, $ignoreInvalidCoupons = true);
}
