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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Warehouse\Model\Cache\ProductAttributeCache;

/**
 * Observer for clearing attribute metadata cache
 */
class ClearAttributeMetadataCacheObserver implements ObserverInterface
{
    /**
     * @param ProductAttributeCache $attributeCache
     */
    public function __construct(
        private ProductAttributeCache $attributeCache
    ) {
    }

    /**
     * Clear attribute metadata cache when an attribute is saved or deleted
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->attributeCache->clearAttributeMetadataCache();
    }
}
