<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Order\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;

class UpdateCodOrderStatus
{
    /**
     * IS CRON ENABLED FOR UPDATE PENDING TO PROCESSING STATUS FOR COD ORDERS
     */
    public const IS_CRON_ENABLED = 'cron_schedule/cod_order_status_update/status';

    /**
     * TIME FOR CHANGE PENDING TO PROCESSING
     */
    public const TIME_TO_PROCESSING = 'cron_schedule/cod_order_status_update/time_to_processing';


    /**
     * Update Processing Status Constructor
     *
     * @param CollectionFactory    $orderCollectionFactory
     * @param OrderRepository      $orderRepository
     * @param CronLogger           $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private CollectionFactory    $orderCollectionFactory,
        private OrderRepository      $orderRepository,
        private CronLogger           $cronLogger,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Function to update order status from 'PENDING' to 'PROCESSING'.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateCodOrderStatus cron started at ' . date('Y-m-d H:i:s'));
            try {
                $minutes = $this->scopeConfig->getValue(self::TIME_TO_PROCESSING, ScopeInterface::SCOPE_STORE);
                $currentDate = date('Y-m-d H:i:s');
                $timeLimit = strtotime('-' . $minutes . 'minutes', strtotime($currentDate));
                $timeLimit = date('Y-m-d H:i:s', $timeLimit);

                $orderCollection = $this->orderCollectionFactory->create();
                $orderCollection->addFieldToFilter('main_table.status', 'pending')
                    ->addFieldToFilter('main_table.created_at', ['lteq' => $timeLimit])
                    ->getSelect()->join(
                        ['payment' => 'sales_order_payment'],
                        'main_table.entity_id = payment.parent_id',
                        ['method']
                    )->where('payment.method = ?', 'cashondelivery');
                
                foreach ($orderCollection as $order) {
                    $order->setState(Order::STATE_PROCESSING)
                        ->setStatus(Order::STATE_PROCESSING)
                        ->setIsConfirmed(1);
                    $order->addCommentToStatusHistory(
                            "Cron : Processing(processing)"
                        );
                    $this->orderRepository->save($order);
                }
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateCodOrderStatus cron ended at ' . date('Y-m-d H:i:s'));
        }
    }


}
