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

use Magento\Framework\Model\AbstractModel;
use Pratech\Promotion\Api\Data\PromoCodeInterface;

class PromoCode extends AbstractModel implements PromoCodeInterface
{
    public const KEY_CODE_ID = 'code_id';
    public const KEY_CAMPAIGN_ID = 'campaign_id';
    public const KEY_PROMO_CODE = 'promo_code';
    public const KEY_TIMES_USED = 'times_used';
    public const KEY_CREATED_AT = 'created_at';

    /**
     * Model constructor
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\PromoCode::class);
    }

    /**
     * @inheritDoc
     */
    public function getCodeId()
    {
        return $this->getData(self::KEY_CODE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCodeId($codeId)
    {
        return $this->setData(self::KEY_CODE_ID, $codeId);
    }

    /**
     * @inheritDoc
     */
    public function getCampaignId()
    {
        return $this->getData(self::KEY_CODE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCampaignId($campaignId)
    {
        return $this->setData(self::KEY_CAMPAIGN_ID, $campaignId);
    }

    /**
     * @inheritDoc
     */
    public function getPromoCode()
    {
        return $this->getData(self::KEY_PROMO_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPromoCode($promoCode)
    {
        return $this->setData(self::KEY_PROMO_CODE, $promoCode);
    }

    /**
     * @inheritDoc
     */
    public function getTimesUsed()
    {
        return $this->getData(self::KEY_TIMES_USED);
    }

    /**
     * @inheritDoc
     */
    public function setTimesUsed($timesUsed)
    {
        return $this->setData(self::KEY_TIMES_USED, $timesUsed);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * Load By Promo Code.
     *
     * @param string $promoCode
     * @return $this
     */
    public function loadByCode(string $promoCode)
    {
        $this->load($promoCode, 'promo_code');
        return $this;
    }
}
