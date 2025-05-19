<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Helper;

use DateTime;
use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Comment\CollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as SalesRuleCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Helper\Eav;
use Pratech\Order\Model\ResourceModel\ShipmentTrackUpdates\CollectionFactory as TrackDetailsCollectionFactory;
use Pratech\Order\Model\ShipmentStatusFactory;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;

/**
 * Data Helper Class for common functions.
 */
class Data
{
    /**
     * Product Entity Type
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * Add StoreCredit After Days Config.
     */
    public const USE_STORE_CREDIT_AFTER_DAYS = 'store_credit/store_credit/use_store_credit_after_days';

    /**
     * Add StoreCredit Title Config.
     */
    public const STORE_CREDIT_TITLE = 'store_credit/store_credit/title';

    /**
     * Associated array of totals array($totalCode => $totalObject)
     *
     * @var array
     */
    protected array $_totals;

    /**
     * Helper Constructor
     *
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param CategoryRepositoryInterface $categoryRepository
     * @param SalesRuleCollectionFactory $salesRuleCollectionFactory
     * @param Config $eavConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $shipmentCommentCollectionFactory
     * @param Eav $eavHelper
     * @param ShipmentStatusFactory $shipmentStatusFactory
     * @param TrackDetailsCollectionFactory $trackDetailsCollectionFactory
     * @param OrderReturnHelper $orderReturnHelper
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        private TimezoneInterface             $timezone,
        private ScopeConfigInterface          $scopeConfig,
        private ProductRepositoryInterface    $productRepository,
        private Logger                        $logger,
        private CategoryRepositoryInterface   $categoryRepository,
        private SalesRuleCollectionFactory    $salesRuleCollectionFactory,
        private Config                        $eavConfig,
        private StoreManagerInterface         $storeManager,
        private CollectionFactory             $shipmentCommentCollectionFactory,
        private Eav                           $eavHelper,
        private ShipmentStatusFactory         $shipmentStatusFactory,
        private TrackDetailsCollectionFactory $trackDetailsCollectionFactory,
        private OrderReturnHelper             $orderReturnHelper,
        private OrderItemRepositoryInterface  $orderItemRepository
    ) {
    }

    /**
     * Get Formatted Order Details
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderDetails(OrderInterface $order): array
    {
        $totalSegments = [];
        $orderStatusLabel = "";
        // Calculate cashback based on config values
        $cashbackAfterDays = $this->scopeConfig->getValue(
            self::USE_STORE_CREDIT_AFTER_DAYS,
            ScopeInterface::SCOPE_STORE
        );
        try {
            $orderStatusLabel = $order->getStatusLabel();
            $totalsSegments = $this->getTotalsSegmentFromOrder($order);

            foreach ($totalsSegments as $totalSegment) {
                $totalSegments[] = $totalSegment;
            }
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return [
            "entity_id" => $order->getEntityId(),
            "platform" => $order->getPlatform(),
            "created_at" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
            "updated_at" => $this->getTimeBasedOnTimezone($order->getUpdatedAt()),
            "customer_email" => $order->getCustomerEmail(),
            "customer_firstname" => $order->getCustomerFirstname(),
            "customer_lastname" => $order->getCustomerLastname(),
            "customer_is_guest" => $order->getCustomerIsGuest(),
            "telephone" => $order->getShippingAddress()->getTelephone(),
            "state" => $order->getState(),
            "status" => $orderStatusLabel,
            "status_code" => $order->getStatus(),
            "increment_id" => $order->getIncrementId(),
            "grand_total" => $order->getGrandTotal(),
            "total_item_count" => $order->getTotalItemCount(),
            "total_qty_ordered" => $order->getTotalQtyOrdered(),
            "items" => $this->getOrderItems($order->getItems()),
            "coupon_code" => $order->getCouponCode(),
            "discount_amount" => $order->getDiscountAmount(),
            "delivery_charges" => $order->getDeliveryCharges(),
            "discount_description" => $order->getDiscountDescription(),
            "shipping_address" => $this->getOrderShippingAddress($order->getShippingAddress()),
            "estimated_delivery_date" => $order->getEstimatedDeliveryDate() ?
                $this->getTimeBasedOnTimezone($order->getEstimatedDeliveryDate())
                : "",
            "payment" => $this->getPaymentDetails($order),
            "total_segments" => $totalSegments,
            "applied_rule" => $order->getAppliedRuleIds() ?
                $this->getAppliedRuleDetails(explode(',', $order->getAppliedRuleIds()))
                : [],
            "earned_cashback" => $order->getEligibleCashback() ? $order->getEligibleCashback() : 0,
            "after_days_cashback" => $cashbackAfterDays,
        ];
    }

    /**
     * Initialize order totals array
     *
     * @param OrderInterface $order
     * @return array $this->_totals
     */
    protected function getTotalsSegmentFromOrder(OrderInterface $order): array
    {
        $this->_totals = [];

        if ((double)$order->getMrpTotal() != 0) {
            $this->_totals['mrp_total'] = [
                'code' => 'mrp_total',
                'title' => __('MRP Total'),
                'value' => $order->getMrpTotal(),
                'area' => null
            ];
        } else {
            $this->_totals['mrp_total'] = [
                'code' => 'mrp_total',
                'title' => __('MRP Total'),
                'value' => $order->getSubtotal(),
                'area' => null
            ];
        }

        if ((double)$order->getBagDiscount() != 0) {
            $this->_totals['bag_discount'] = [
                'code' => 'bag_discount',
                'title' => __('Discount on MRP'),
                'value' => $order->getBagDiscount(),
                'area' => null
            ];
        }

        if ((double)$order->getDiscountAmount() != 0) {
            $this->_totals['discount'] = [
                'code' => 'discount',
                'title' => __('Offer Applied'),
                'value' => $order->getDiscountAmount(),
                'area' => null
            ];
        }

        $this->_totals['delivery_charges'] = [
            'code' => 'delivery_charges',
            'title' => __('Shipping & Handling'),
            'value' => $order->getDeliveryCharges() ? $order->getDeliveryCharges() : 0.00,
            'area' => null
        ];

        if ((double)$order->getGrandTotalWithoutPrepaid() != 0) {
            $this->_totals['grand_total_without_prepaid'] = [
                'code' => 'grand_total_without_prepaid',
                'title' => __('Total'),
                'value' => $order->getGrandTotalWithoutPrepaid(),
                'area' => null
            ];
        } else {
            $this->_totals['grand_total_without_prepaid'] = [
                'code' => 'grand_total_without_prepaid',
                'title' => __('Total'),
                'value' => $order->getGrandTotal(),
                'area' => null
            ];
        }

        $this->_totals['prepaid_discount'] = [
            'code' => 'prepaid_discount',
            'title' => __('Prepaid Discount'),
            'value' => $order->getPrepaidDiscount(),
            'area' => null
        ];

        // Include HL Reward Point total segment if it exists
        if ((double)$order->getCustomerBalanceAmount() != 0) {
            $this->_totals['customerbalance'] = [
                'code' => 'customerbalance',
                'title' => __($this->getStoreCreditLabel()),
                'value' => -$order->getCustomerBalanceAmount(),
                'area' => null
            ];
        }

        $this->_totals['grand_total'] = [
            'code' => 'grand_total',
            'title' => __('Grand Total'),
            'value' => $order->getGrandTotal(),
            'area' => 'footer'
        ];

        return $this->_totals;
    }

    /**
     * Return Store Credit Title to be shown in FE.
     *
     * @return mixed
     */
    public function getStoreCreditLabel(): mixed
    {
        return $this->scopeConfig->getValue(
            self::STORE_CREDIT_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Time Based On Timezone
     *
     * @param string $date
     * @return string
     */
    public function getTimeBasedOnTimezone(string $date): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format('j M, o');
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Order Items
     *
     * @param array $items
     * @return array
     */
    public function getOrderItems(array $items): array
    {
        $orderItems = [];
        $image = $primaryL1Category = $primaryL2Category = $brand = $slug = "";

        /** @var OrderItemInterface $item */
        foreach ($items as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $image = $product->getImage();
                $slug = $product->getUrlKey();
                if ($product->getCustomAttribute('primary_l1_category')) {
                    $primaryL1CategoryId = $product->getCustomAttribute('primary_l1_category')->getValue();
                    $primaryL1Category = $this->getCategoryData($primaryL1CategoryId);
                }
                if ($product->getCustomAttribute('primary_l2_category')) {
                    $primaryL2CategoryId = $product->getCustomAttribute('primary_l2_category')->getValue();
                    $primaryL2Category = $this->getCategoryData($primaryL2CategoryId);
                }
                if ($product->getCustomAttribute('brand')) {
                    $brand = $this->getProductAttributeLabel(
                        'brand',
                        $product->getCustomAttribute('brand')->getValue()
                    );
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->error($e->getMessage() . __METHOD__);
            }

            $orderItems[] = [
                "name" => $item->getName(),
                "original_price" => (float)$item->getOriginalPrice(),
                "price" => (float)$item->getPrice(),
                "product_type" => $item->getProductType(),
                "qty_ordered" => $item->getQtyOrdered(),
                "row_total" => $item->getRowTotal(),
                "sku" => $item->getSku(),
                "image" => $image,
                "slug" => $slug,
                "primary_l1_category" => $primaryL1Category,
                "primary_l2_category" => $primaryL2Category,
                "brand" => $brand,
                "estimated_delivery_time" => $item->getEstimatedDeliveryTime(),
                "warehouse_code" => $item->getWarehouseCode()
            ];
        }

        return $orderItems;
    }

    /**
     * Get Category Data.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryData(int $categoryId): array
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return [
                'name' => $category->getName(),
                'slug' => $category->getUrlKey()
            ];
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Get Product Attribute Label
     *
     * @param string $attributeCode
     * @param string $optionValue
     * @return mixed|string
     */
    public function getProductAttributeLabel(string $attributeCode, string $optionValue): ?string
    {
        try {
            $attribute = $this->eavConfig->getAttribute(self::ENTITY_TYPE, $attributeCode);
            $attributeOptions = $attribute->getSource()->getAllOptions();

            foreach ($attributeOptions as $option) {
                if ($option['value'] == $optionValue) {
                    return $option['label'];
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }

        return "";
    }

    /**
     * Get Order Shipping Address
     *
     * @param Address $shippingAddress
     * @return string
     */
    public function getOrderShippingAddress(Address $shippingAddress): string
    {
        return $shippingAddress->getName() . ", " . implode(", ", $shippingAddress->getStreet())
            . ", " . $shippingAddress->getCity() . ", " . $shippingAddress->getRegion()
            . " - " . $shippingAddress->getPostcode();
    }

    /**
     * Get Payment Details
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getPaymentDetails(OrderInterface $order): array
    {
        if ($order->getPayment()) {
            $payment = $order->getPayment();
            return [
                "additional_information" => $payment->getAdditionalInformation(),
                "amount_ordered" => $payment->getAmountOrdered(),
                "base_amount_ordered" => $payment->getBaseAmountOrdered(),
                "method" => $payment->getMethod(),
                "rzp_order_id" => $order->getRzpOrderId(),
                "rzp_payment_id" => $order->getRzpPaymentId(),
            ];
        }
        return [];
    }

    /**
     * Get Applied Rule Details
     *
     * @param array $ruleIds
     * @return array
     */
    public function getAppliedRuleDetails(array $ruleIds): array
    {
        $ruleData = [];
        $salesRuleCollection = $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('rule_id', ['in' => [$ruleIds]]);

        if (!empty($salesRuleCollection)) {
            foreach ($salesRuleCollection->getData() as $salesRule) {
                $ruleData[] = [
                    'rule_id' => $salesRule['rule_id'],
                    'rule_name' => $salesRule['name'],
                    'coupon' => $salesRule['code'],
                    'rule_description' => $salesRule['description'],
                    'term_and_conditions' => $salesRule['term_and_conditions']
                ];
            }
        }
        return $ruleData;
    }

    /**
     * Get Formatted Order Details
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getCustomerOrderDetails(OrderInterface $order): array
    {
        $totalSegments = [];
        $orderStatusLabel = "";
        // Calculate cashback based on config values
        $cashbackAfterDays = $this->scopeConfig->getValue(
            self::USE_STORE_CREDIT_AFTER_DAYS,
            ScopeInterface::SCOPE_STORE
        );
        try {
            $orderStatusLabel = $order->getStatusLabel();
            $totalsSegments = $this->getTotalsSegmentFromOrder($order);
            foreach ($totalsSegments as $totalSegment) {
                $totalSegments[] = $totalSegment;
            }
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        $orderData = [
            "entity_id" => $order->getEntityId(),
            "increment_id" => $order->getIncrementId(),
            "updated_at" => $this->getTimeBasedOnTimezone($order->getUpdatedAt()),
            "created_at" => $this->getDateTimeBasedOnTimezone($order->getCreatedAt()),
            "status" => $orderStatusLabel,
            "status_code" => $order->getStatus(),
            "items" => $this->getCustomerOrderItems($order->getItems()),
            "shipments" => $this->getOrderShipment($order),
            "contact_information" => [
                "mobile_number" => $order->getShippingAddress()->getTelephone(),
                "email_address" => $order->getCustomerEmail()
            ],
            "payment_information" => $this->getPaymentDetails($order),
            "order_information" => [
                "placed_on" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                "increment_id" => $order->getIncrementId()
            ],
            "total_segments" => $totalSegments,
            "shipping_address" => $this->getCustomerShippingAddressForOrder($order->getShippingAddress()),
            "estimated_delivery_date" => $order->getEstimatedDeliveryDate() ?
                $this->getTimeBasedOnTimezone($order->getEstimatedDeliveryDate())
                : "",
            "applied_rule" => $order->getAppliedRuleIds() ?
                $this->getAppliedRuleDetails(explode(',', $order->getAppliedRuleIds()))
                : [],
            "earned_cashback" => $order->getEligibleCashback() ? $order->getEligibleCashback() : 0,
            "after_days_cashback" => $cashbackAfterDays,
        ];

        if ($order->getStatus() == 'payment_failed') {
            $orderData['cancellation_reason'] = "Payment Failed";
        } elseif ($order->getStatus() == 'canceled') {
            $statusHistoryItem = $order->getStatusHistoryCollection()->getFirstItem();
            $comment = $statusHistoryItem->getComment();
            $orderData['cancellation_reason'] = $comment;
        }

        return $orderData;
    }

    /**
     * Get Time Based On Timezone for Email
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public function getDateTimeBasedOnTimezone(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format($format);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Order Items
     *
     * @param array $items
     * @return array
     */
    public function getCustomerOrderItems(array $items): array
    {
        $orderItems = [];

        /** @var OrderItemInterface $item */
        foreach ($items as $item) {
            $productSlug = "";
            try {
                $product = $this->productRepository->getById($item->getProductId());
                $productSlug = $product->getUrlKey();
            } catch (NoSuchEntityException $e) {
            }
            $qtyRemaining = $item->getQtyOrdered() - $item->getQtyShipped() - $item->getQtyCanceled();
            $itemInfo = [
                "item_id" => $item->getItemId(),
                "sku" => $item->getSku(),
                "name" => $item->getName(),
                "original_price" => (float)$item->getOriginalPrice(),
                "price" => (float)$item->getPrice(),
                "product_type" => $item->getProductType(),
                "image" => $this->getProductImage($item->getSku()),
                "slug" => $productSlug,
                "estimated_delivery_time" => (int)$item->getEstimatedDeliveryTime(),
                "warehouse_code" => $item->getWarehouseCode()
            ];
            if ((int)$qtyRemaining > 0) {
                $itemInfo['qty'] = (int)$qtyRemaining;
                $orderItems['processing'][] = $itemInfo;
            }
            if ((int)$item->getQtyCanceled()) {
                $itemInfo['qty'] = (int)$item->getQtyCanceled();
                $orderItems['canceled'][] = $itemInfo;
            }
            if ((int)$item->getQtyReturned()) {
                $itemInfo['qty'] = (int)$item->getQtyReturned();
                $orderItems['returned'][] = $itemInfo;
            }
            if ((int)$item->getQtyRefunded()) {
                $itemInfo['qty'] = (int)$item->getQtyRefunded();
                $orderItems['refunded'][] = $itemInfo;
            }
        }
        return $orderItems;
    }

    /**
     * Get Shipment Item Product Image.
     *
     * @param string $sku
     * @return string
     */
    public function getProductImage(string $sku): string
    {
        try {
            $product = $this->productRepository->get($sku);
            $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if ($product->getImage()) {
                return $mediaBaseUrl . 'catalog/product' . $product->getImage();
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error($sku . "does not exist. " . $e->getMessage() . __METHOD__);
        }
        return "";
    }

    /**
     * Get Order Shipment.
     *
     * @param Order $order
     * @return array
     */
    public function getOrderShipment(Order $order): array
    {
        $processingDate = $this->getTimeBasedOnTimezone($order->getCreatedAt());
        $shipmentCollection = $order->getShipmentsCollection();
        $shipments = [];
        /** @var Shipment $shipment */
        foreach ($shipmentCollection as $shipment) {
            $shipments[] = [
                "status" => $this->getShipmentStatus($shipment->getShipmentStatus()),
                "increment_id" => $shipment->getIncrementId(),
                "created_at" => $this->getTimeBasedOnTimezone($shipment->getCreatedAt()),
                "updated_at" => $this->getTimeBasedOnTimezone($shipment->getUpdatedAt()),
                "items" => $this->getShipmentItems($shipment->getItems()),
                "tracking" => $this->getTrackingInfo($shipment, $processingDate),
                "track_details" => $this->getTrackDetails($shipment),
                "entity_id" => $shipment->getId(),
                "is_rated" => (bool)$shipment->getIsRated(),
                "rating" => $shipment->getRating(),
                "review" => $shipment->getReview() ?: '',
                "keywords" => $shipment->getKeywords() ?: '',
                "returns" => $this->orderReturnHelper->getOrderReturns($order, $shipment->getId()),
                'is_return_eligible' => $this->orderReturnHelper->isReturnEligible($shipment)
            ];
        }
        return $shipments;
    }

    /**
     * Get Shipment Status.
     *
     * @param int|null $shipmentStatusId
     * @return string
     */
    public function getShipmentStatus(?int $shipmentStatusId): string
    {
        $status = $this->shipmentStatusFactory->create()->load($shipmentStatusId);
        if (!empty($status->getStatusId())) {
            return $status->getStatusCode();
        }
        return 'shipped';
    }

    /**
     * Get Shipment Items Details.
     *
     * @param ShipmentItemInterface[] $shipmentItems
     * @return array
     */
    public function getShipmentItems(array $shipmentItems): array
    {
        $categorization = "";
        $qtyReturned = 0;
        $items = [];
        foreach ($shipmentItems as $shipmentItem) {
            $productSlug = "";
            try {
                $orderItem = $this->orderItemRepository->get($shipmentItem->getOrderItemId());
                $qtyReturned = (int)$orderItem->getQtyReturned();

                $product = $this->productRepository->getById($shipmentItem->getProductId());
                $productSlug = $product->getUrlKey();
                if ($product->getCustomAttribute('categorization')) {
                    $categorization = $this->eavHelper->getOptionLabel(
                        'categorization',
                        $product->getCustomAttribute('categorization')->getValue()
                    );
                }
            } catch (NoSuchEntityException $e) {
            }
            $items[] = [
                "item_id" => $shipmentItem->getOrderItemId(),
                "sku" => $shipmentItem->getSku(),
                "name" => $shipmentItem->getName(),
                "image" => $this->getProductImage($shipmentItem->getSku()),
                "qty" => (int)$shipmentItem->getQty(),
                "price" => (float)$shipmentItem->getPrice(),
                "slug" => $productSlug,
                "categorization" => $categorization,
                "qty_returned" => (int)$qtyReturned
            ];
        }
        return $items;
    }

    /**
     * Get Tracking Info to display Track Items in order detail page.
     *
     * @param Shipment $shipment
     * @param string $processingDate
     * @return array
     */
    public function getTrackingInfo(Shipment $shipment, string $processingDate): array
    {
        $trackingInfo[] = [
            'status' => "processing",
            'comment' => "Processing",
            'created_at' => $processingDate
        ];

        /** @var Collection $shipmentCollection */
        $shipmentCommentCollection = $this->shipmentCommentCollectionFactory->create()
            ->addFieldToSelect(['comment', 'status', 'created_at'])
            ->addFieldToFilter('parent_id', ['eq' => $shipment->getId()]);

        foreach ($shipmentCommentCollection as $shipmentComment) {
            $trackingInfo[] = [
                'status' => $shipmentComment->getStatus(),
                'comment' => $shipmentComment->getComment(),
                'created_at' => $this->getTimeBasedOnTimezone($shipmentComment->getCreatedAt())
            ];
        }

        return $trackingInfo;
    }

    /**
     * Get Track Details to display Tracking details in order detail page.
     *
     * @param Shipment $shipment
     * @return array
     */
    public function getTrackDetails(Shipment $shipment): array
    {
        $trackDetails = [];

        $trackCollection = $this->trackDetailsCollectionFactory->create()
            ->addFieldToFilter('parent_id', $shipment->getId())
            ->setOrder('created_at', 'DESC');
        if ($trackCollection->getSize() > 0) {
            foreach ($trackCollection as $trackItem) {
                $status = $this->shipmentStatusFactory->create()->load($trackItem->getStatusId());
                $trackDetails[] = [
                    "location" => $trackItem->getLocation(),
                    "remark" => $trackItem->getRemark(),
                    "status" => $status->getStatus(),
                    "status_code" => $status->getStatusCode(),
                    "date" => $this->getDateTimeBasedOnTimezone($trackItem->getCreatedAt(), 'M d, Y'),
                    "time" => $this->getDateTimeBasedOnTimezone($trackItem->getCreatedAt(), 'H:m')
                ];
            }
        }

        return $trackDetails;
    }

    /**
     * Get Customer's Order Shipping Address
     *
     * @param Address $shippingAddress
     * @return array
     */
    public function getCustomerShippingAddressForOrder(Address $shippingAddress): array
    {
        return [
            "name" => $shippingAddress->getName(),
            "address" => implode(", ", $shippingAddress->getStreet()) . ", " . $shippingAddress->getCity()
                . ", " . $shippingAddress->getRegion(),
            "post_code" => $shippingAddress->getPostcode()
        ];
    }

    /**
     * Get Order Data For Refund
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderDataForRefund(OrderInterface $order): array
    {
        return [
            "entity_id" => $order->getEntityId(),
            "status_code" => $order->getStatus(),
            "customer_firstname" => $order->getCustomerFirstname(),
            "customer_lastname" => $order->getCustomerLastname(),
            "contact_information" => [
                "mobile_number" => $order->getShippingAddress()->getTelephone(),
                "email_address" => $order->getCustomerEmail()
            ],
            "payment_information" => $this->getPaymentDetails($order),
            "order_information" => [
                "placed_on" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                "increment_id" => $order->getIncrementId()
            ],
        ];
    }

    /**
     * Get Formatted Order Details
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderDetailForDPanda(OrderInterface $order): array
    {
        $totalSegments = [];
        $totalsSegments = $this->getTotalsSegmentFromOrder($order);
        foreach ($totalsSegments as $totalSegment) {
            $totalSegments[] = $totalSegment;
        }
        return [
            "created_at" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
            "updated_at" => $this->getTimeBasedOnTimezone($order->getUpdatedAt()),
            "customer_email" => $order->getCustomerEmail(),
            "customer_firstname" => $order->getCustomerFirstname(),
            "customer_lastname" => $order->getCustomerLastname(),
            "telephone" => $order->getShippingAddress()->getTelephone(),
            "increment_id" => $order->getIncrementId(),
            "grand_total" => $order->getGrandTotal(),
            "total_item_count" => $order->getTotalItemCount(),
            "total_qty_ordered" => $order->getTotalQtyOrdered(),
            "items" => $this->getOrderItems($order->getItems()),
            "coupon_code" => $order->getCouponCode(),
            "discount_amount" => $order->getDiscountAmount(),
            "delivery_charges" => $order->getDeliveryCharges(),
            "discount_description" => $order->getDiscountDescription(),
            "shipping_address" => $this->getOrderShippingAddress($order->getShippingAddress()),
            "estimated_delivery_date" => $order->getEstimatedDeliveryDate() ?
                $this->getTimeBasedOnTimezone($order->getEstimatedDeliveryDate())
                : "",
            "payment" => $this->getPaymentDetails($order),
            "total_segments" => $totalSegments,
            "applied_rule" => $order->getAppliedRuleIds() ?
                $this->getAppliedRuleDetails(explode(',', $order->getAppliedRuleIds()))
                : []
        ];
    }
}
