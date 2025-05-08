<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Model\Total;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * FreeGift Discount Total Segment
 */
class FreeGiftDiscount extends AbstractTotal
{
    /**
     * FreeGiftDiscount Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Collect Totals
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     * @throws NoSuchEntityException
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
        if (!empty($quote->getAppliedRuleIds())) {
            $discountAmount = 0;
            $freeItems = 0;
            foreach ($quote->getAllVisibleItems() as $item) {
                $product = $this->getProduct($item->getSku());
                $discountAmount -= ($product->getFinalPrice() * $item->getQty()) - ($item->getPrice() *
                        $item->getQty());
                if ($item->getPrice() == 0) {
                    $freeItems++;
                }
            }
            if ($freeItems != 0) {
                $total->setDiscountAmount($total->getDiscountAmount() + $discountAmount);
                $total->setBaseDiscountAmount($total->getBaseDiscountAmount() + $discountAmount);
                $quote->setDiscountAmount($quote->getDiscountAmount() + $discountAmount);
                $quote->setBaseDiscountAmount($quote->getBaseDiscountAmount() + $discountAmount);
            }
        }
        return $this;
    }

    /**
     * Get Product By SKU
     *
     * @param string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct(string $sku): ProductInterface
    {
        return $this->productRepository->get($sku);
    }
}
