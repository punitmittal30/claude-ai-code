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

namespace Pratech\Promotion\Api\Data;

interface PromoCodeGenerationSpecInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    public const CODE_FORMAT_ALPHANUMERIC = 'alphanum';
    public const CODE_FORMAT_ALPHABETICAL = 'alpha';
    public const CODE_FORMAT_NUMERIC = 'num';

    /**
     * Get the id of the campaign associated with the promo code
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
     * Get format of generated promo code
     *
     * @return string
     */
    public function getFormat();

    /**
     * Set format for generated promo code
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format);

    /**
     * Number of promos to generate
     *
     * @return int
     */
    public function getQuantity();

    /**
     * Set number of promos to generate
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity($quantity);

    /**
     * Get length of promo code
     *
     * @return int
     */
    public function getLength();

    /**
     * Set length of promo code
     *
     * @param int $length
     * @return $this
     */
    public function setLength($length);
}
