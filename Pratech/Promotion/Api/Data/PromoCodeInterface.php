<?php

namespace Pratech\Promotion\Api\Data;

/**
 * Interface PromoCodeInterface
 *
 * @api
 * @since 100.0.2
 */
interface PromoCodeInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get code id
     *
     * @return int|null
     */
    public function getCodeId();

    /**
     * Set code id
     *
     * @param int $codeId
     * @return $this
     */
    public function setCodeId($codeId);

    /**
     * Get the id of the campaign associated with the code
     *
     * @return int
     */
    public function getCampaignId();

    /**
     * Set campaign id
     *
     * @param int $campaignId
     * @return $this
     */
    public function setCampaignId($campaignId);

    /**
     * Get promo code
     *
     * @return string|null
     */
    public function getPromoCode();

    /**
     * Set promo code
     *
     * @param string $promoCode
     * @return $this
     */
    public function setPromoCode($promoCode);

    /**
     * Get the number of times the promo has been used
     *
     * @return int
     */
    public function getTimesUsed();

    /**
     * Set time used.
     *
     * @param int $timesUsed
     * @return $this
     */
    public function setTimesUsed($timesUsed);

    /**
     * Date when the promo is created
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set the date the promo is created
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}
