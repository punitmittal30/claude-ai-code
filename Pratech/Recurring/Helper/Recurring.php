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
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Recurring\Api\SubscriptionRepositoryInterface;
use Pratech\Recurring\Model\Config\Source\Duration as RecurringDuration;
use Pratech\Recurring\Model\Config\Source\DurationType as RecurringDurationType;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;
use Pratech\Recurring\Model\Subscription;
use Pratech\Recurring\Model\SubscriptionFactory;
use Pratech\Recurring\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

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
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     * @param RecurringDuration $recurringDuration
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private OrderRepositoryInterface $orderRepository,
        private ScopeConfigInterface $scopeConfig,
        private Logger $logger,
        private BaseHelper $baseHelper,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private RecurringDuration $recurringDuration,
        private SubscriptionFactory $subscriptionFactory,
        private SubscriptionCollectionFactory $subscriptionCollectionFactory
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
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return $result;
        }

        try {
            $customerId = $order->getCustomerId();
            $itemsInfo = [];
            foreach ($order->getAllItems() as $item) {
                $productId = $item->getProductId();
                $previouslySubscribedCollection = $this->subscriptionCollectionFactory->create()
                    ->addFieldToFilter('product_id', $productId)
                    ->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('status', SubscriptionStatus::ENABLED);
                $previouslySubscribed = ($previouslySubscribedCollection->getSize() > 0) ? true : false;

                $product = $this->getProduct($item->getSku());
                $isRecurrable = $product->getCustomAttribute('is_recurrable')
                    ? (bool)$product->getCustomAttribute('is_recurrable')->getValue()
                    : false;

                $itemsInfo[] = [
                    'order_item_id' => (int)$item->getItemId(),
                    'product_id' => (int)$productId,
                    'product_name' => $item->getName(),
                    'product_sku' => $item->getSku(),
                    'previously_subscribed' => $previouslySubscribed,
                    'is_recurrable' => $isRecurrable
                ];
            }

            $durationOptions = $this->recurringDuration->getAllOptions();
            $result = [
                'is_recurring_enabled' => $this->isRecurringEnabled(),
                'can_subscribe_multiple_times' => $this->canSubscribeMultipleTimes(),
                'items' => $itemsInfo,
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
     * @param int $orderId
     * @param array $items
     * @return bool
     */
    public function createSubscription(int $orderId, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return false;
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

            // $shippingAddress = $order->getShippingAddress();
            foreach ($order->getAllItems() as $item) {
                $itemId = $item->getItemId();
                if (!isset($requestItemsData[$itemId])) {
                    continue;
                }

                $duration = $requestItemsData[$itemId]['duration'];
                $durationType = $requestItemsData[$itemId]['duration_type'];
                $validTill = $this->getValidTill($duration, $durationType);
                $subscriptionData = [
                    "order_id" => $orderId,
                    "product_id" => $item->getProductId(),
                    "product_name" => $item->getName(),
                    "product_sku" => $item->getSku(),
                    "customer_id" => $order->getCustomerId(),
                    "customer_name" => $order->getCustomerFirstname() . " " . $order->getCustomerLastname(),
                    "duration" => $duration,
                    "duration_type" => $durationType,
                    "locked_price" => $item->getPrice(),
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
            }

            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
        }

        return false;
    }

    /**
     * Get Valid Till Date of subscription
     *
     * @param string $duration
     * @param string $durationType
     * @return string
     */
    public function getValidTill($duration, $durationType)
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
}
