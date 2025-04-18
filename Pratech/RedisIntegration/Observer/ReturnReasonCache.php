<?php

namespace Pratech\RedisIntegration\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Pratech\RedisIntegration\Model\SystemConfigurationRedisCache;

class ReturnReasonCache implements ObserverInterface
{
    /**
     * Update Cache Constructor
     *
     * @param SystemConfigurationRedisCache $systemConfigurationRedisCache
     * @param RedisCacheLogger              $redisCacheLogger
     */
    public function __construct(
        private SystemConfigurationRedisCache $systemConfigurationRedisCache,
        private RedisCacheLogger   $redisCacheLogger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $this->systemConfigurationRedisCache->deleteOrderReturnReasonsCache();
        } catch (Exception $exception) {
            $this->redisCacheLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
