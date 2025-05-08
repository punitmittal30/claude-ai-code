<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Observer;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Observer on product delete after event.
 */
class ProductDeleteAfter implements ObserverInterface
{
    /**
     * @param Logger    $apiLogger
     * @param SqsEvent  $sqsEvent
     */
    public function __construct(
        private Logger $apiLogger,
        private SqsEvent $sqsEvent
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Product $product
        */
        $product = $observer->getEvent()->getData('product');
        try {
            $this->sqsEvent->sendCatalogEvent(['skus' => $product->getSku()], 'CATALOG_PRODUCT_DELETED');
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
