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

namespace Pratech\Order\Cron;

use Exception;
use Magento\CustomerBalance\Observer\RevertStoreCreditForOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Order\Helper\Order;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Cron to move payment_review orders to payment_failed.
 */
class UpdatePaymentReviewOrderStatus
{

    /**
     * IS CRON ENABLED FOR UPDATE PAYMENT REVIEW STATUS
     */
    public const IS_CRON_ENABLED = 'cron_schedule/payment_review_order_status/status';

    /**
     * Update Payment Review Order Status Constructor
     *
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     * @param Order $orderHelper
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRedisCache $customerRedisCache
     * @param RevertStoreCreditForOrder $revertStoreCreditForOrder
     * @param SqsEvent $sqsEvent
     */
    public function __construct(
        private CollectionFactory         $orderCollectionFactory,
        private OrderRepository           $orderRepository,
        private Order                     $orderHelper,
        private CronLogger                $cronLogger,
        private ScopeConfigInterface      $scopeConfig,
        private CustomerRedisCache        $customerRedisCache,
        private RevertStoreCreditForOrder $revertStoreCreditForOrder,
        private SqsEvent                  $sqsEvent,
    ) {
    }

    /**
     * Execute function to move payment_review orders to payment_failed.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info(
                'UpdatePaymentReviewOrderStatus cron started at ' . date('Y-m-d H:i:s')
            );
            try {
                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('status', ['eq' => 'payment_review'])
                    ->addFieldToFilter('created_at', ['lteq' => $this->getCurrentDateMinusThirtyMinutes()])
                    ->getAllIds();
                foreach ($orders as $orderId) {
                    $this->updatePaymentFailedStatus($orderId);
                }
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info(
                'UpdatePaymentReviewOrderStatus cron ended at ' . date('Y-m-d H:i:s')
            );
        }
    }

    /**
     * Get Current Date
     *
     * @return string
     */
    public function getCurrentDateMinusThirtyMinutes(): string
    {
        $today = date('Y-m-d H:i:s');
        return date("Y-m-d H:i:s", strtotime('-' . 30 . ' minutes', strtotime($today)));
    }

    /**
     * Update Payment Failed Status.
     *
     * @param int $orderId
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function updatePaymentFailedStatus(int $orderId): void
    {
        $order = $this->orderRepository->get($orderId);
        $this->cronLogger->info(
            $order->getIncrementId() . ' ------ '
            . $order->getCreatedAt() . ' ------ '
            . $this->getCurrentDateMinusThirtyMinutes()
        );
        try {
            $failedOrder = $this->orderHelper->cancelOrderItems($order);
            $failedOrder->addCommentToStatusHistory(
                "API : Payment Failed(payment_failed)"
                . " | Source : Cron"
                . " | Razorpay Order ID : NIL"
                . " | Razorpay Payment ID : NIL",
                $order->getStatus(),
                true
            );
            $failedOrder->setIsConfirmed(0);
            $this->orderRepository->save($failedOrder);

            $this->revertStoreCreditForOrder->execute($order);
            $this->customerRedisCache->deleteCustomerStoreCreditTransactions($order->getCustomerId());

            $emailData = $this->orderHelper->getOrderDataForEmail($order, 'CRON_ORDER_PAYMENT_FAILED');
            $this->sqsEvent->sentEmailEventToSqs($emailData);
        } catch (Exception $exception) {
            $this->cronLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
