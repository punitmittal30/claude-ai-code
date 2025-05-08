<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Recurring\Api\Data;

interface SubscriptionMappingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get SubscriptionMapping list.
     *
     * @return \Pratech\Recurring\Api\Data\SubscriptionMappingInterface[]
     */
    public function getItems();

    /**
     * Set subscription_id list.
     *
     * @param \Pratech\Recurring\Api\Data\SubscriptionMappingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
