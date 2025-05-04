<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Order\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Order\Helper\Order;

class UpdateProcessingStatus
{
    /**
     * IS CRON ENABLED FOR UPDATE PROCESSING STATUS TO PACKED
     */
    public const IS_CRON_ENABLED = 'cron_schedule/packed_order_status/status';

    /**
     * End Time After Processing
     */
    public const START_TIME_AFTER_PROCESSING = 'cron_schedule/packed_order_status/start_time_after_processing';

    /**
     * End Time After Processing
     */
    public const END_TIME_AFTER_PROCESSING = 'cron_schedule/packed_order_status/end_time_after_processing';

    /**
     * Update Processing Status Constructor
     *
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     * @param Order $orderHelper
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private CollectionFactory    $orderCollectionFactory,
        private OrderRepository      $orderRepository,
        private Order                $orderHelper,
        private CronLogger           $cronLogger,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Function to update order status from 'Processing' to 'Item(s) packed in warehouse if not updated within 5 hours.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateProcessingStatus cron started at ' . date('Y-m-d H:i:s'));
            try {
                $interval = $this->getCurrentDateMinusStartToEndHours();
                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToSelect('entity_id')
                    ->addFieldToFilter('status', ['eq' => 'processing'])
                    ->addFieldToFilter('updated_at', ['from' => $interval['gteq'], 'to' => $interval['lt']])
                    ->getAllIds();
                foreach ($orders as $orderId) {
                    $this->orderHelper->packedOrder($orderId);
                }
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateProcessingStatus cron ended at ' . date('Y-m-d H:i:s'));
        }
    }

    /**
     * Get Current Date Minus Start To End Hours.
     *
     * @return array
     */
    public function getCurrentDateMinusStartToEndHours(): array
    {
        $startTime = $this->scopeConfig->getValue(self::START_TIME_AFTER_PROCESSING, ScopeInterface::SCOPE_STORE);
        $endTime = $this->scopeConfig->getValue(self::END_TIME_AFTER_PROCESSING, ScopeInterface::SCOPE_STORE);

        $currentDate = date('Y-m-d H:i:s');

        $dateMinusStartHours = strtotime('-' . $startTime . 'hours', strtotime($currentDate));
        $dateMinusEndHours = strtotime('-' . $endTime . 'hours', strtotime($currentDate));

        $formattedDateMinusStartHours = date('Y-m-d H:i:s', $dateMinusStartHours);
        $formattedDateMinusEndHours = date('Y-m-d H:i:s', $dateMinusEndHours);

        return [
            'gteq' => $formattedDateMinusEndHours, // Greater than or equal to date minus end hours
            'lt' => $formattedDateMinusStartHours, // Less than date minus start hours
        ];
    }
}
