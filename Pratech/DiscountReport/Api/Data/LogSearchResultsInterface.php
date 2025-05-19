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

interface LogSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Log list.
     *
     * @return LogInterface[]
     */
    public function getItems();

    /**
     * Set quote_id list.
     *
     * @param LogInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
