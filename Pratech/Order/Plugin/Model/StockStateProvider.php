<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Plugin\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockStateProvider as ProductStockStateProvider;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Math\Division as MathDivision;
use Pratech\Base\Logger\Logger;

/**
 * Check Product Stock
 */
class StockStateProvider extends ProductStockStateProvider
{

    /**
     * Product Stock State Provider Constructor
     *
     * @param MathDivision $mathDivision
     * @param FormatInterface $localeFormat
     * @param ObjectFactory $objectFactory
     * @param ProductFactory $productFactory
     * @param ProductRepository $productRepository
     * @param Logger $apiLogger
     * @param bool $qtyCheckApplicable
     */
    public function __construct(
        MathDivision $mathDivision,
        FormatInterface $localeFormat,
        ObjectFactory $objectFactory,
        ProductFactory $productFactory,
        private ProductRepository $productRepository,
        private Logger $apiLogger,
        $qtyCheckApplicable = true
    ) {
        parent::__construct($mathDivision, $localeFormat, $objectFactory, $productFactory, $qtyCheckApplicable);
    }

    /**
     * @inheritDoc
     */
    public function checkQuoteItemQty(
        StockItemInterface $stockItem,
        $qty,
        $summaryQty,
        $origQty = 0
    ) {
        $result = parent::checkQuoteItemQty($stockItem, $qty, $summaryQty, $origQty);

        $errorCode = $result->getErrorCode();
        if ($errorCode == "qty_available") {
            $productId = $stockItem->getProductId();
            $message = __(
                'Only %1 qty left for the selected product',
                $stockItem->getQty()
            );

            $result->setMessage($message);
            $result->setQuoteMessage($message);
        }
        return $result;
    }

    /**
     * Get Product Name By Id
     *
     * @param  int $id
     * @return string
     */
    public function getProductNameById(int $id): string
    {
        try {
            return $this->productRepository->getById($id)->getName();
        } catch (NoSuchEntityException $e) {
            $this->apiLogger->error($e->getLogMessage() . __METHOD__);
        }
        return '';
    }
}
