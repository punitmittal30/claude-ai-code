<?php
/**
 * Pratech_SlackNotifier
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SlackNotifier
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SlackNotifier\Cron;

use DateTime;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Return\Helper\OrderReturn;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory as ReturnCollectionFactory;
use Pratech\SlackNotifier\Helper\SlackNotifier;

class ReturnReport
{
    /**
     * Is cron enabled for update store_credit.
     */
    public const IS_CRON_ENABLED = 'cron_schedule/return_report/status';


    /**
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezone
     * @param SlackNotifier $slackNotifier
     * @param OrderReturn $orderReturnHelper
     * @param ReturnCollectionFactory $returnCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        private CronLogger                   $cronLogger,
        private ScopeConfigInterface         $scopeConfig,
        private TimezoneInterface            $timezone,
        private SlackNotifier                $slackNotifier,
        private OrderReturn                  $orderReturnHelper,
        private ReturnCollectionFactory      $returnCollectionFactory,
        private OrderRepositoryInterface     $orderRepository,
        private OrderItemRepositoryInterface $orderItemRepository,
        private Filesystem                   $filesystem,
        private DirectoryList                $directoryList
    )
    {
    }

    /**
     * Cron to update storeCredit.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('ReturnReport cron started at ' . date('Y-m-d H:i:s'));
            try {

                $csvData = [];

                $returnCollection = $this->returnCollectionFactory->create()
                    ->addFieldToFilter('status', ['nin' => [37, 38]])
                    ->addFieldToFilter('refund_status', ['in' => [41]]);

                foreach ($returnCollection as $returnRequest) {
                    $returnData = $this->orderReturnHelper->getReturnRequest($returnRequest->getId());

                    if (empty($returnData)) {
                        continue;
                    }

                    try {
                        $order = $this->orderRepository->get($returnData['order_id']);
                        $customerDetails = $this->orderReturnHelper->getCustomerDetailsById($order->getCustomerId());
                    } catch (Exception $e) {
                        $this->cronLogger->error("Error loading order: " . $e->getMessage());
                        continue;
                    }

                    $paymentDetails = $this->orderReturnHelper->getPaymentDetailsByRequestId($returnData['request_id']);

                    foreach ($returnData['return_items'] as $item) {
                        try {
                            $orderItem = $this->orderItemRepository->get($item['order_item_id']);

                            $csvData[] = [
                                'Order ID' => $order->getIncrementId(),
                                'Discription' => $item['name'],
                                'Email ID' => $order->getCustomerEmail(),
                                'SKU' => $orderItem->getSku(),
                                'Quantity' => $item['return_qty'],
                                'Reason' => $item['reason'],
                                'Return ID' => $returnData['request_id'],
                                'Prepaid/Manual' => ($paymentDetails && isset($paymentDetails['payment_type']))
                                    ? 'Manual' : 'Prepaid',
                                'Amount' => $item['refunded_amount'],
                                'Return Status' => $returnData['status'],
                                'Refund status' => $returnData['refund_status'],
                                'Return Update date' => $this->formatDate($returnRequest->getModifiedAt()),
                                'UPI Details' => $paymentDetails['upi_id'] ?? '',
                                'User Name' => $customerDetails['name'] ?? '',
                                'Bank Account' => $paymentDetails['account_number'] ?? '',
                                'IFSC code' => $paymentDetails['ifsc_code'] ?? ''
                            ];
                        } catch (Exception $e) {
                            $this->cronLogger->error("Error processing item: " . $e->getMessage());
                        }
                    }
                }
                if (!empty($csvData)) {
                    $this->sendReportCsv($csvData);
                }


            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('ReturnReport cron ended at ' . date('Y-m-d H:i:s'));
        }
    }

    /**
     * Format Date
     *
     * @param string $date
     * @return string
     */
    private function formatDate(string $date): string
    {
        try {
            return $this->timezone->date(new DateTime($date))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Send Report Csv to Slack Channel
     *
     * @param array $csvData
     * @return void
     */
    private function sendReportCsv(array $csvData): void
    {
        try {
            $headers = array_keys($csvData[0]);

            $csvContent = $this->arrayToCsv($headers, $csvData);

            $filename = 'return_report_' . date('Y-m-d_His') . '.csv';

            if ($this->slackNotifier->sendCsvContent($csvContent, $filename)) {
                $this->cronLogger->info('CSV report sent successfully');
            } else {
                $this->cronLogger->error('Failed to send CSV report');
            }
        } catch (Exception $e) {
            $this->cronLogger->error('CSV creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Array To Csv
     *
     * @param array $headers
     * @param array $data
     * @return string
     */
    private function arrayToCsv(array $headers, array $data): string
    {
        $output = fopen('php://temp', 'r+');

        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
