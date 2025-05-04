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
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Observer on product delete after event.
 */
class ProductImportBunchDeleteAfter implements ObserverInterface
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
        try {
            $skus = [];
            $bunch = $observer->getEvent()->getData('bunch');
            foreach ($bunch as $product) {
                if (isset($product[ImportProduct::COL_SKU])) {
                    $skus[] = $product[ImportProduct::COL_SKU];
                }
            }
            $this->sqsEvent->sendCatalogEvent(['skus' => implode(',', $skus)], 'CATALOG_PRODUCT_DELETED');
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
