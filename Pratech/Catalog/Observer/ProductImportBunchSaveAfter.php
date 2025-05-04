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
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Model\ResourceModel\LinkedProduct as LinkedProductResource;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Observer on product save after event.
 */
class ProductImportBunchSaveAfter implements ObserverInterface
{
    /**
     * @param Logger                $apiLogger
     * @param SqsEvent              $sqsEvent
     * @param ProductResource       $productResource
     * @param LinkedProductResource $linkedProductResource
     */
    public function __construct(
        private Logger                $apiLogger,
        private SqsEvent              $sqsEvent,
        private ProductResource       $productResource,
        private LinkedProductResource $linkedProductResource
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
                try {
                    if (isset($product['linked_products'])) {
                        $productSku = $product['sku'];
                        $linkedSkus = explode(',', $product['linked_products']);

                        $productId = $this->productResource->getIdBySku($productSku);

                        $linkedProductIds = [];
                        foreach ($linkedSkus as $sku) {
                            $linkedId = $this->productResource->getIdBySku($sku);
                            if ($linkedId) {
                                $linkedProductIds[] = (int)$linkedId;
                            }
                        }
                        $this->linkedProductResource->saveLinks($productId, $linkedProductIds);
                    }
                } catch (Exception $e) {
                    $this->apiLogger->error($e->getMessage() . __METHOD__);
                    continue;
                }

            }
            $this->sqsEvent->sendCatalogEvent(['skus' => implode(',', $skus)], 'CATALOG_PRODUCT_UPDATED');
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
