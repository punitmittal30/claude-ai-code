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

namespace Pratech\Promotion\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Model\Data\Response;
use Pratech\Promotion\Api\CampaignManagementInterface;
use Pratech\Promotion\Helper\Campaign;

class CampaignManagement implements CampaignManagementInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * PROMOTION API RESOURCE
     */
    private const PROMOTION_API_RESOURCE = 'promotion';

    /**
     * @param Response $response
     * @param Campaign $campaignHelper
     */
    public function __construct(
        private Response $response,
        private Campaign $campaignHelper
    ) {
    }

    /**
     * Credit Balance to User Account.
     *
     * @param string $promoCode
     * @param int $customerId
     * @param string $campaignType
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function creditBalance(string $promoCode, int $customerId, string $campaignType): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::PROMOTION_API_RESOURCE,
            $this->campaignHelper->creditBalance($promoCode, $customerId, $campaignType)
        );
    }
}
