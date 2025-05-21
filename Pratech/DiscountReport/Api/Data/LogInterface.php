<?php
/**
 * Pratech_DiscountReport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\DiscountReport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\DiscountReport\Api\Data;

interface LogInterface
{
    public const LOG_ID = 'log_id';
    public const QUOTE_ID = 'quote_id';
    public const ITEM_SKU = 'item_sku';
    public const DISCOUNT_DATA = 'discount_data';

    /**
     * Get log_id
     *
     * @return string|null
     */
    public function getLogId();

    /**
     * Set log_id
     *
     * @param string $logId
     * @return LogInterface
     */
    public function setLogId($logId);

    /**
     * Get quote_id
     *
     * @return string|null
     */
    public function getQuoteId();

    /**
     * Set quote_id
     *
     * @param string $quoteId
     * @return LogInterface
     */
    public function setQuoteId($quoteId);

    /**
     * Get item_sku
     *
     * @return string|null
     */
    public function getItemSku();

    /**
     * Set item_sku
     *
     * @param string $itemSku
     * @return LogInterface
     */
    public function setItemSku($itemSku);

    /**
     * Get discount_data
     *
     * @return string|null
     */
    public function getDiscountData();

    /**
     * Set discount_data
     *
     * @param string $discountData
     * @return LogInterface
     */
    public function setDiscountData($discountData);
}
