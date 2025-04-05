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
 * Observer for clearing product attribute cache
 */
class ClearProductAttributeCacheObserver implements ObserverInterface
{
    /**
     * @param ProductAttributeCache $attributeCache
     */
    public function __construct(
        private ProductAttributeCache $attributeCache
    ) {
    }

    /**
     * Clear product attribute cache when product is saved
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($product && $product->getId()) {
            $this->attributeCache->clearProductCache((int)$product->getId());
        }
    }
}
