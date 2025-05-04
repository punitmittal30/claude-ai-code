<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Cron;

use DateTime;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory as ReturnCollectionFactory;

class UpdateInstantRefundFlag
{
    public const IS_CRON_ENABLED = 'cron_schedule/update_instant_refund_flag/status';

    public const CONFIG_PATH_DAYS_THRESHOLD = 'cron_schedule/update_instant_refund_flag/day_threshold';


    /**
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param ReturnCollectionFactory $returnCollectionFactory
     * @param RequestRepositoryInterface $requestRepository
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        private CronLogger                 $cronLogger,
        private ScopeConfigInterface       $scopeConfig,
        private ReturnCollectionFactory    $returnCollectionFactory,
        private RequestRepositoryInterface $requestRepository,
        private OrderReturnHelper          $orderReturnHelper
    )
    {
    }

    /**
     * Cron to update return instant refund status.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        $this->cronLogger->info('UpdateInstantRefundFlag cron started at ' . date('Y-m-d H:i:s'));

        try {
            $refundPendingStatus = $this->orderReturnHelper->getInitialRefundStatusId();
            $days = (int)$this->orderReturnHelper->getConfig(self::CONFIG_PATH_DAYS_THRESHOLD);
            $fourDaysAgo = (new DateTime())->modify("-{$days} days")->format('Y-m-d H:i:s');

            $collection = $this->returnCollectionFactory->create()
                ->addFieldToFilter('refund_status', $refundPendingStatus)
                ->addFieldToFilter('is_processed', 1)
                ->addFieldToFilter('instant_refund', 1)
                ->addFieldToFilter('created_at', ['lteq' => $fourDaysAgo]);

            if ($collection->getSize() > 0) {
                foreach ($collection as $returnRequest) {
                    try {
                        $returnRequest->setInstantRefund(0);
                        $this->requestRepository->save($returnRequest);
                        $this->cronLogger->info('Updated return ID: ' . $returnRequest->getId());
                    } catch (Exception $e) {
                        $this->cronLogger->error("Error updating return ID {$returnRequest->getId()}: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            $this->cronLogger->error('UpdateInstantRefundFlag error: ' . $e->getMessage());
        }

        $this->cronLogger->info('UpdateInstantRefundFlag cron ended at ' . date('Y-m-d H:i:s'));
    }
}
