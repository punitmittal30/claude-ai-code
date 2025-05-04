<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Helper;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\DiscountReport\Model\ResourceModel\Log\CollectionFactory as DiscountLogCollectionFactory;
use Pratech\Recurring\Api\SubscriptionRepositoryInterface;
use Pratech\Recurring\Model\Config\Source\Duration as RecurringDuration;
use Pratech\Recurring\Model\Config\Source\DurationType as RecurringDurationType;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;
use Pratech\Recurring\Model\Subscription;
use Pratech\Recurring\Model\SubscriptionFactory;
use Pratech\Recurring\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use Pratech\Recurring\Model\SubscriptionMappingFactory;

/**
 * Recurring helper class.
 */
class Recurring
{
    /**
     * IS RECURRING ENABLED
     */
    public const IS_RECURRING_ENABLED = 'recurring/general_settings/enable';

    /**
     * IS DISCOUNT ENABLED
     */
    public const IS_DISCOUNT_ENABLED = 'recurring/general_settings/enable_discount';

    /**
     * IS CASHBACK ENABLED
     */
    public const IS_CASHBACK_ENABLED = 'recurring/general_settings/enable_cashback';

    /**
     * MAXIMUM TOTAL TIME
     */
    public const MAX_TOTAL_TIME = 'recurring/general_settings/max_total_time';

    /**
     * CANCEL SUBSCRIPTION ALLOWED
     */
    public const CANCEL_SUBSCRIPTION = 'recurring/customer_control_settings/cancel_subscription';

    /**
     * SUBSCRIBE MULTIPLE TIMES
     */
    public const SUBSCRIBE_MULTIPLE_TIMES = 'recurring/customer_control_settings/multiple_subscription';

    /**
     * Recurring Helper Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param BaseHelper $baseHelper
     * @param DiscountLogCollectionFactory $discountLogCollectionFactory
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     * @param RecurringDuration $recurringDuration
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionMappingFactory $subscriptionMappingFactory
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private OrderRepositoryInterface $orderRepository,
        private ScopeConfigInterface $scopeConfig,
        private Logger $logger,
        private BaseHelper $baseHelper,
        private DiscountLogCollectionFactory $discountLogCollectionFactory,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private RecurringDuration $recurringDuration,
        private SubscriptionFactory $subscriptionFactory,
        private SubscriptionCollectionFactory $subscriptionCollectionFactory,
        private SubscriptionMappingFactory $subscriptionMappingFactory
    ) {
    }

    /**
     * Function isRecurringEnabled
     *
     * @return boolean
     */
    public function isRecurringEnabled(): bool
    {
        $isRecurringEnabled = (bool)$this->scopeConfig->getValue(
            self::IS_RECURRING_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        return $isRecurringEnabled;
    }

    /**
     * Function isDiscountEnabled
     *
     * @return boolean
     */
    public function isDiscountEnabled(): bool
    {
        $isDiscountEnabled = (bool)$this->scopeConfig->getValue(
            self::IS_DISCOUNT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        return $isDiscountEnabled;
    }

    /**
     * Function isCashbackEnabled
     *
     * @return boolean
     */
    public function isCashbackEnabled(): bool
    {
        $isCashbackEnabled = (bool)$this->scopeConfig->getValue(
            self::IS_CASHBACK_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        return $isCashbackEnabled;
    }

    /**
     * Function canCancelSubscription
     *
     * @return boolean
     */
    public function canCancelSubscription(): bool
    {
        $canCancelSubscription = (bool)$this->scopeConfig->getValue(
            self::CANCEL_SUBSCRIPTION,
            ScopeInterface::SCOPE_STORE
        );
        return $canCancelSubscription;
    }

    /**
     * Function canSubscribeMultipleTimes
     *
     * @return boolean
     */
    public function canSubscribeMultipleTimes(): bool
    {
        $canSubscribeMultipleTimes = (bool)$this->scopeConfig->getValue(
            self::SUBSCRIBE_MULTIPLE_TIMES,
            ScopeInterface::SCOPE_STORE
        );
        return $canSubscribeMultipleTimes;
    }

    /**
     * Get Subscription Form Data
     *
     * @param int $orderId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSubscriptionFormData(int $orderId): array
    {
        $result = [];
        if (!$this->isRecurringEnabled()) {
            return ['is_recurring_enabled' => false];
        }

        $order = $this->orderRepository->get($orderId);
        try {
            $itemsInfo = [];
            foreach ($order->getAllItems() as $item) {
                $product = $this->getProduct($item->getSku());
                list($isRecurringEligible, $message) = $this->getRecurringEligibility($order, $item, $product);

                $lockedPrice = $this->calculateLockedPrice($order, $item);
                $floorPrice = $product->getCustomAttribute('floor_price')
                    ? (float)$product->getCustomAttribute('floor_price')->getValue()
                    : 0;

                $itemsInfo[] = [
                    'order_item_id' => (int)$item->getItemId(),
                    'product_id' => (int)$item->getProductId(),
                    'product_name' => $item->getName(),
                    'product_sku' => $item->getSku(),
                    'locked_price' => $lockedPrice,
                    'floor_price' => $floorPrice,
                    'is_recurring_eligible' => $isRecurringEligible,
                    'message' => $message
                ];
            }

            $durationOptions = $this->recurringDuration->getAllOptions();
            $maxTotalTime = (int)$this->scopeConfig->getValue(
                self::MAX_TOTAL_TIME,
                ScopeInterface::SCOPE_STORE
            );
            $result = [
                'is_recurring_enabled' => $this->isRecurringEnabled(),
                'items' => $itemsInfo,
                'max_total_time' => $maxTotalTime,
                'duration_options' => $durationOptions
            ];
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Create Subscription
     *
     * @param int $customerId
     * @param int $orderId
     * @param array $items
     * @return bool
     */
    public function createSubscription(int $customerId, int $orderId, array $items): bool
    {
        $createFlag = false;
        if (empty($items) || !$this->isRecurringEnabled()) {
            return $createFlag;
        }

        $order = $this->orderRepository->get($orderId);
        if ($order->getCustomerId() != $customerId) {
            return $createFlag;
        }
        try {
            $requestItemsData = [];
            foreach ($items as $requestItem) {
                $orderItemId = $requestItem['order_item_id'];
                $durationType = $requestItem['duration_type'] ?? RecurringDurationType::DAY;
                $maxRepeat = $requestItem['max_repeat'] ?? 3;
                $requestItemsData[$orderItemId] = [
                    'product_qty' => $requestItem['product_qty'],
                    'duration' => $requestItem['duration'],
                    'duration_type' => $durationType,
                    'max_repeat' => $maxRepeat
                ];
            }

            foreach ($order->getAllItems() as $item) {
                $itemId = $item->getItemId();
                if (!isset($requestItemsData[$itemId])) {
                    continue;
                }

                $product = $this->getProduct($item->getSku());
                list($isRecurringEligible) = $this->getRecurringEligibility($order, $item, $product);
                if (!$isRecurringEligible) {
                    continue;
                }

                $lockedPrice = $this->calculateLockedPrice($order, $item);
                $duration = $requestItemsData[$itemId]['duration'];
                $durationType = $requestItemsData[$itemId]['duration_type'];
                $validTill = $this->getValidTill($duration, $durationType);
                $subscriptionData = [
                    "order_id" => $orderId,
                    "product_id" => $item->getProductId(),
                    "product_name" => $item->getName(),
                    "product_sku" => $item->getSku(),
                    "customer_id" => $customerId,
                    "customer_name" => $order->getCustomerFirstname() . " " . $order->getCustomerLastname(),
                    "duration" => $duration,
                    "duration_type" => $durationType,
                    "locked_price" => $lockedPrice,
                    "product_qty" => $requestItemsData[$itemId]['product_qty'],
                    "max_repeat" => $requestItemsData[$itemId]['max_repeat'],
                    "payment_code" => "cashondelivery",
                    "status" => SubscriptionStatus::ENABLED,
                    "order_item_id" => $itemId,
                    "valid_till" => $validTill
                ];

                $subscriptionModel = $this->subscriptionFactory->create();
                $subscriptionModel->setData($subscriptionData);
                $this->subscriptionRepository->save($subscriptionModel);
                $createFlag = true;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            $createFlag = false;
        }

        return $createFlag;
    }

    /**
     * Function to get recurring eligibility
     *
     * @param SalesOrder $order
     * @param OrderItem $item
     * @param ProductInterface $product
     * @return array
     */
    public function getRecurringEligibility(SalesOrder $order, OrderItem $item, ProductInterface $product): array
    {
        $isRecurrable = $product->getCustomAttribute('is_recurrable')
            ? (bool)$product->getCustomAttribute('is_recurrable')->getValue()
            : false;
        if (!$isRecurrable) {
            return [false, __('The product is not recurrable.')];
        }

        $canSubscribeMultipleTimes = $this->canSubscribeMultipleTimes();
        $previouslySubscribedCollection = $this->subscriptionCollectionFactory->create()
            ->addFieldToFilter('product_id', $item->getProductId())
            ->addFieldToFilter('customer_id', $order->getCustomerId())
            ->addFieldToFilter('status', SubscriptionStatus::ENABLED);
        $previouslySubscribed = ($previouslySubscribedCollection->getSize() > 0) ? true : false;
        if (!$canSubscribeMultipleTimes && $previouslySubscribed) {
            return [false, __('You already have active subscription for this product.')];
        }

        return [true, ''];
    }

    /**
     * Calculate locked price
     *
     * @param SalesOrder $order
     * @param OrderItem $item
     * @return float
     */
    public function calculateLockedPrice(SalesOrder $order, OrderItem $item): float
    {
        $lockedPrice = $item->getPrice();
        $quoteId = $order->getQuoteId();
        $itemSku = $item->getSku();
        $discountLogCollection = $this->discountLogCollectionFactory->create()
            ->addFieldToFilter('quote_id', ['eq' => $quoteId])
            ->addFieldToFilter('item_sku', ['eq' => $itemSku]);
        if (!$discountLogCollection->getSize()) {
            return round($lockedPrice, 2);
        }

        $discountLog = $discountLogCollection->getFirstItem();
        $discountDataArray = $discountLog->getDiscountData()
            ? json_decode($discountLog->getDiscountData(), true)
            : [];

        $discountAmount = abs($item->getBaseDiscountAmount());
        $subTotal = $item->getBaseRowTotal();

        $prepaidDiscountArray = $discountDataArray['prepaid_discount'] ?? [];
        $prepaidDiscountAmount = 0;
        foreach ($prepaidDiscountArray as $discountKey => $discountData) {
            if (!empty((float)$order->getBasePrepaidDiscount())) {
                $prepaidDiscountAmount = $discountData['amount'];
            }
        }

        $customerBalanceArray = $discountDataArray['customerbalance'] ?? [];
        $customerBalanceAmount = 0;
        foreach ($customerBalanceArray as $discountKey => $discountData) {
            if (!empty((float)$order->getCustomerBalanceAmount())) {
                $customerBalanceAmount = $discountData['amount'];
            }
        }

        $calculatedSubTotal = $subTotal - $discountAmount + $customerBalanceAmount;
        $lockedPrice = $calculatedSubTotal / (int)$item->getQtyOrdered();
        return round($lockedPrice, 2);
    }

    /**
     * Get Valid Till Date of subscription
     *
     * @param string $duration
     * @param string $durationType
     * @return string
     */
    private function getValidTill($duration, $durationType)
    {
        $validTill = '';
        switch ($durationType) {
            case RecurringDurationType::DAY:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::DAY));
                break;
            case RecurringDurationType::WEEK:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::WEEK));
                break;
            case RecurringDurationType::MONTH:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::MONTH));
                break;
            case RecurringDurationType::YEAR:
                $validTill = date('Y-m-d', strtotime(' + ' . $duration . RecurringDurationType::YEAR));
                break;
        }
        return $validTill;
    }

    /**
     * Get Customer Subscriptions
     *
     * @param int $customerId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCustomerSubscriptions(int $customerId): array
    {
        $result = [];
        try {
            $subscriptionsArray = [];
            $subscriptionCollection = $this->subscriptionCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId);
            foreach ($subscriptionCollection as $subscription) {
                $subscriptionType = "Every " . $subscription->getDuration()
                    . " " . $subscription->getDurationType() . "s";
                $statusLabel = $subscription->getStatus() ? __("Subscribed") : __("UnSubscribed");
                $createdAt = $this->baseHelper->getDateTimeBasedOnTimezone($subscription->getCreatedAt());
                $validTill = $subscription->getValidTill()
                    ? $this->baseHelper->getDateTimeBasedOnTimezone($subscription->getValidTill())
                    : "";

                $subscriptionOrders = [];
                $subscriptionMappingCollection = $this->subscriptionMappingFactory->create()->getCollection()
                    ->addFieldToFilter('subscription_id', $subscription->getId());
                foreach ($subscriptionMappingCollection as $subscriptionMapping) {
                    $subscriptionOrders[] = [
                        'order_id' => $subscriptionMapping->getOrderId(),
                        'created_at' => $this->baseHelper->getDateTimeBasedOnTimezone($subscription->getCreatedAt())
                    ];
                }

                $subscriptionsArray[] = [
                    'subscription_id' => $subscription->getId(),
                    'master_order_id' => $subscription->getOrderId(),
                    'product_id' => $subscription->getProductId(),
                    'product_name' => $subscription->getProductName(),
                    'product_sku' => $subscription->getProductSku(),
                    'product_qty' => $subscription->getProductQty(),
                    'subscription_type' => $subscriptionType,
                    'duration' => $subscription->getDuration(),
                    'duration_type' => $subscription->getDurationType(),
                    'max_repeat' => $subscription->getMaxRepeat(),
                    'locked_price' => $subscription->getLockedPrice(),
                    'status' => $subscription->getStatus(),
                    'status_label' => $statusLabel,
                    'created_at' => $createdAt,
                    'next_order_date' => $validTill,
                    'cancellation_reason' => $subscription->getCancellationReason() ?? '',
                    'subscription_orders' => $subscriptionOrders
                ];
            }
            $result = [
                'subscriptions' => $subscriptionsArray,
                'can_cancel_subscription' => $this->canCancelSubscription()
            ];
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Cancel Customer Subscription
     *
     * @param int $customerId
     * @param int $subscriptionId
     * @param string $reason
     * @return bool
     */
    public function cancelCustomerSubscription(int $customerId, int $subscriptionId, string $reason = ''): bool
    {
        $cancelFlag = false;
        if (!$this->canCancelSubscription()) {
            return $cancelFlag;
        }

        $subscription = $this->subscriptionRepository->get($subscriptionId);
        if ($subscription->getCustomerId() != $customerId) {
            return $cancelFlag;
        }
        try {
            $subscription->setStatus(SubscriptionStatus::DISABLED)
                ->setCancellationReason($reason)
                ->setId($subscription->getId());
            $this->subscriptionRepository->save($subscription);
            $cancelFlag = true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
        }

        return $cancelFlag;
    }

    /**
     * Get Product By SKU
     *
     * @param  string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct(string $sku): ProductInterface
    {
        return $this->productRepository->get($sku);
    }

    /**
     * This function will write the data into the log file
     *
     * @param array|mixed $data
     */
    public function logDataInLogger($data)
    {
        $this->logger->info($data);
    }

    /**
     * This function will write the error into the log file
     *
     * @param array|mixed $data
     */
    public function logErrorInLogger($data)
    {
        $this->logger->error($data);
    }
}
