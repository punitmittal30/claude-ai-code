<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\WarehouseRedisCache;

class WarehouseSaveAfter implements ObserverInterface
{
    /**
     * Update Cache Constructor
     *
     * @param WarehouseRedisCache $warehouseRedisCache
     * @param RedisCacheLogger $redisCacheLogger
     */
    public function __construct(
        private WarehouseRedisCache $warehouseRedisCache,
        private RedisCacheLogger    $redisCacheLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $this->warehouseRedisCache->deleteWarehouseUrlList();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
