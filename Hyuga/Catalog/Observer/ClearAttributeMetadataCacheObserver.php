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

use Hyuga\Catalog\Model\Cache\ProductAttributeCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

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
     * Clear attribute metadata cache when an attribute is saved, deleted, or modified
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->attributeCache->clearAttributeMetadataCache();
    }
}
