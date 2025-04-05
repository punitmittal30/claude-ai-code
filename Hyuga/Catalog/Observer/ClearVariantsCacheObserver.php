<?php
/**
 * Hyuga_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Catalog\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer for clearing configurable variants cache
 */
class ClearVariantsCacheObserver implements ObserverInterface
{
    /**
     * @param CacheInterface $cache
     */
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * Clear variants cache when product is saved or deleted
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Simply clear the entire variants cache tag
        $this->cache->clean(['configurable_product_variants']);
    }
}
