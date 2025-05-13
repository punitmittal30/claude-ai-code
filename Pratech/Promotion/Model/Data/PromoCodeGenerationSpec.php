<?php

namespace Pratech\Promotion\Model\Data;

class PromoCodeGenerationSpec extends \Magento\Framework\Api\AbstractExtensibleObject implements
    \Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterface
{
    public const KEY_CAMPAIGN_ID = 'campaign_id';
    public const KEY_FORMAT = 'format';
    public const KEY_LENGTH = 'length';
    public const KEY_QUANTITY = 'quantity';

    /**
     * Get the id of the campaign associated with the promo code
     *
     * @return int
     */
    public function getCampaignId()
    {
        return $this->_get(self::KEY_CAMPAIGN_ID);
    }

    /**
     * Set campaign id
     *
     * @param int $campaignId
     * @return $this
     */
    public function setCampaignId($campaignId)
    {
        return $this->setData(self::KEY_CAMPAIGN_ID, $campaignId);
    }

    /**
     * Get format of generated promo code
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->_get(self::KEY_FORMAT);
    }

    /**
     * Set format for generated promo code
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        return $this->setData(self::KEY_FORMAT, $format);
    }

    /**
     * Number of promo codes to generate
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->_get(self::KEY_QUANTITY);
    }

    /**
     * Set number of promo codes to generate
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        return $this->setData(self::KEY_QUANTITY, $quantity);
    }

    /**
     * Get length of promo code
     *
     * @return int
     */
    public function getLength()
    {
        return $this->_get(self::KEY_LENGTH);
    }

    /**
     * Set length of promo code
     *
     * @param int $length
     * @return $this
     */
    public function setLength($length)
    {
        return $this->setData(self::KEY_LENGTH, $length);
    }
}
