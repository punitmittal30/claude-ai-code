<?php
/**
 * Pratech_BagDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\BagDiscount
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\BagDiscount\Model\Total;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Pratech\Catalog\Helper\Product as ProductHelper;

/**
 * Bag Discount Total Segment
 */
class BagDiscount extends AbstractTotal
{
    /**
     * BagDiscount Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ProductHelper              $productHelper
     */
    public function __construct(
        private ProductRepositoryInterface   $productRepository,
        private ProductHelper $productHelper
    ) {
        $this->setCode('bag_discount');
    }

    /**
     * Collect Totals
     *
     * @param  Quote                       $quote
     * @param  ShippingAssignmentInterface $shippingAssignment
     * @param  Total                       $total
     * @return $this
     */
    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $amount = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $this->getProduct($item->getSku());
            if ($product->getSpecialPrice() && $this->productHelper->getIsSpecialPriceExist($product)) {
                $amount -= ($product->getPrice() - $product->getSpecialPrice()) * $item->getQty();
            }
        }
        $total->setBagDiscount($amount);
        $total->setBaseBagDiscount($amount);
        $quote->setBagDiscount($amount);
        $quote->setBaseBagDiscount($amount);

        return $this;
    }

    /**
     * Get Product By SKU
     *
     * @param  string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct(string $sku): ProductInterface
    {
        return $this->productRepository->get($sku);
    }

    /**
     * Fetch Totals Segment
     *
     * @param  Quote $quote
     * @param  Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total): array
    {
        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $quote->getBagDiscount()
        ];
    }

    /**
     * Get Label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Discount on MRP');
    }
}
