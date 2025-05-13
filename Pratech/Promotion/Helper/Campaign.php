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

namespace Pratech\Promotion\Helper;

use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Promotion\Model\CampaignFactory;
use Pratech\Promotion\Model\PromoCodeFactory;
use Pratech\Promotion\Model\PromoCodeUsageFactory;
use Pratech\Promotion\Model\ResourceModel\PromoCode;
use Pratech\Promotion\Model\ResourceModel\PromoCodeUsage;
use Pratech\StoreCredit\Helper\Data;

class Campaign
{
    public const WEBSITE_ID = 1;

    /**
     * @param PromoCode $promoCodeResource
     * @param PromoCodeFactory $promoCodeFactory
     * @param PromoCodeUsageFactory $promoCodeUsageFactory
     * @param CampaignFactory $campaignFactory
     * @param PromoCodeUsage $promoCodeUsageResource
     * @param Data $storeCreditHelper
     */
    public function __construct(
        private PromoCode             $promoCodeResource,
        private PromoCodeFactory      $promoCodeFactory,
        private PromoCodeUsageFactory $promoCodeUsageFactory,
        private CampaignFactory       $campaignFactory,
        private PromoCodeUsage        $promoCodeUsageResource,
        private Data                  $storeCreditHelper
    ) {
    }

    /**
     * Credit Store Credit Balance To Customer Account.
     *
     * @param string $promoCode
     * @param int $customerId
     * @param string $campaignType
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function creditBalance(string $promoCode, int $customerId, string $campaignType): array
    {
        $msg = "Try Before You Buy Claim";

        $promoCodeModel = $this->promoCodeFactory->create()->loadByCode($promoCode);
        $promoCodeData = $promoCodeModel->getData();

        if (!empty($promoCodeData)) {
            $promoCodeUsageByCustomerId = $this->promoCodeUsageFactory->create()
                ->loadByCustomerId($customerId)->getData();

            /** @var \Pratech\Promotion\Model\Campaign $campaignModel */
            $campaignModel = $this->campaignFactory->create()->load($promoCodeData['campaign_id']);

            if (!empty($promoCodeUsageByCustomerId) && $campaignType == $campaignModel->getType()) {
                return [
                    "is_credited" => false,
                    "message" => "You can claim HCash benefit only once"
                ];
            }

            if ($promoCodeData['times_used'] > 0) {
                return [
                    "is_credited" => false,
                    "message" => "You already claimed HCash benefit"
                ];
            }

            $this->storeCreditHelper->addStoreCredit(
                $customerId,
                $campaignModel->getAmount(),
                $msg,
                [
                    'event_name' => 'try_before_you_buy',
                    'promo_code' => $promoCode
                ]
            );

            $timesUsed = $promoCodeData['times_used'] + 1;
            $promoCodeModel->setTimesUsed($timesUsed);
            $this->promoCodeResource->save($promoCodeModel);
            $this->promoCodeUsageResource->updateCustomerPromoCodeTimesUsed($customerId, $promoCodeData['code_id']);
            return [
                "is_credited" => true,
                "message" => "Yay! you have unlocked " . $campaignModel->getAmount() . " cashback",
                "cashback_amount" => $campaignModel->getAmount()
            ];
        } else {
            return [
                "is_credited" => false,
                "message" => "Please enter a valid code"
            ];
        }
    }
}
