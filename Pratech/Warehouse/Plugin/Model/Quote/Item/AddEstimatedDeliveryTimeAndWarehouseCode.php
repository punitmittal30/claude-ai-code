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

declare(strict_types=1);

namespace Pratech\Warehouse\Plugin\Model\Quote\Item;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Pratech\Warehouse\Service\DeliveryDateCalculator;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add estimated delivery time to order items
 */
class AddEstimatedDeliveryTimeAndWarehouseCode
{
    /**
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private DeliveryDateCalculator     $deliveryDateCalculator,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface            $logger
    ) {
    }

    /**
     * Set estimated delivery time for order item after it's converted from quote item
     *
     * @param ToOrderItem $subject
     * @param OrderItem $orderItem
     * @param AbstractItem $quoteItem
     * @param array $data
     * @return OrderItem
     */
    public function afterConvert(
        ToOrderItem  $subject,
        OrderItem    $orderItem,
        AbstractItem $quoteItem,
        array        $data = []
    ): OrderItem {
        try {
            $sku = $orderItem->getSku();

            // Get customer shipping address and extract pincode
            $shippingAddress = $quoteItem->getQuote()->getShippingAddress();
            $pincode = null;

            if ($shippingAddress && $shippingAddress->getPostcode()) {
                $pincode = (int)$shippingAddress->getPostcode();
            }

            // If pincode not available in shipping address, try to get from customer default shipping address
            if (!$pincode) {
                $this->logger->error('Pincode not available in shipping address.');
            }

            // Calculate and set delivery estimate if pincode is available
            if ($pincode && $sku) {
                $isDropship = (int)$this->productRepository->get($sku)
                    ?->getCustomAttribute('is_dropship')
                    ?->getValue();
                $estimatedDelivery = $this->deliveryDateCalculator->getEstimatedDelivery($sku, $pincode, $isDropship);
                if ($estimatedDelivery && isset($estimatedDelivery['delivery_time'])) {
                    $orderItem->setEstimatedDeliveryTime((int)$estimatedDelivery['delivery_time']);
                    $orderItem->setWarehouseCode($estimatedDelivery['warehouse_code']);
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error setting estimated delivery time: ' . $e->getMessage(), [
                'sku' => $orderItem->getSku()
            ]);
        }

        return $orderItem;
    }
}
