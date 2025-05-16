<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory as ReturnCollectionFactory;
use Pratech\Return\Model\VinculumIntegration;

class UpdateReturnTracking
{

    /**
     * Is cron enabled for update order return request.
     */
    public const IS_CRON_ENABLED = 'cron_schedule/update_return_track_number/status';

    /**
     * @param Curl $curl
     * @param CronLogger $cronLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param VinculumIntegration $vinculumIntegration
     * @param ReturnCollectionFactory $returnCollectionFactory
     * @param RequestRepositoryInterface $requestRepository
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        private Curl                       $curl,
        private CronLogger                 $cronLogger,
        private ScopeConfigInterface       $scopeConfig,
        private VinculumIntegration        $vinculumIntegration,
        private ReturnCollectionFactory    $returnCollectionFactory,
        private RequestRepositoryInterface $requestRepository,
        private JsonHelper                 $jsonHelper
    ) {
    }

    /**
     * Cron to update order return request.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->cronLogger->info('UpdateReturnTracking cron started at ' . date('Y-m-d H:i:s'));
            try {
                $timeThreshold = strtotime('-10 minutes');

                $returnCollection = $this->returnCollectionFactory->create();

                $returnCollection->getSelect()->joinLeft(
                    ['tracking' => 'sales_order_return_tracking'],
                    'main_table.request_id = tracking.request_id',
                    ['tracking_number' => 'tracking.tracking_number']
                )
                    ->where('tracking.tracking_number IS NULL')
                    ->where('main_table.is_processed = ?', 1)
                    ->where('main_table.modified_at <= ?', date('Y-m-d H:i:s', $timeThreshold));
                if ($returnCollection->getSize() > 0) {
                    foreach ($returnCollection as $returnRequest) {
                        try {
                            $trackingInfo = $this->getTrackingNumber($returnRequest->getVinReturnNumber());

                            if ($trackingInfo['tracking_number']) {
                                $tracking = $this->requestRepository->getEmptyTrackingModel();
                                $tracking->setTrackingCode($trackingInfo['tracking_code'])
                                    ->setTrackingNumber($trackingInfo['tracking_number'])
                                    ->setIsCustomer(false)
                                    ->setRequestId($returnRequest->getId());
                                $this->requestRepository->saveTracking($tracking);
                            }
                        } catch (Exception $e) {
                            $this->cronLogger->error($e->getMessage() . __METHOD__);
                        }
                    }
                }
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage() . __METHOD__);
            }
            $this->cronLogger->info('UpdateReturnTracking cron ended at ' . date('Y-m-d H:i:s'));
        }
    }

    /**
     * Get Tracking Number
     *
     * @param string $returnNo
     * @return array
     */
    private function getTrackingNumber(string $returnNo): array
    {
        $trackingInfo = [
            'tracking_number' => "",
            'tracking_code' => ""
        ];
        $url = $this->vinculumIntegration->getReturnTrackNoApiUrl();

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $body = [
            'RequestBody' => $this->jsonHelper->jsonEncode(
                [
                    'return_no' => [$returnNo],
                    'pageNumber' => 1,
                ]
            ),
            'ApiOwner' => $this->vinculumIntegration->getOwner(),
            'ApiKey' => $this->vinculumIntegration->getKey(),
        ];

        // Set up the CURL request
        $this->curl->setHeaders($headers);
        $this->curl->post($url, http_build_query($body));
        $this->cronLogger->info("Body: ", $body);
        $response = $this->jsonHelper->jsonDecode($this->curl->getBody(), true);
        $this->cronLogger->info("Response: ", $response);

        if (isset($response['response'])) {
            if (isset($response['response']['order'][0]['return_tracking_no'])) {
                $trackingInfo['tracking_number'] = $response['response']['order'][0]['return_tracking_no'];
            }
            if (isset($response['response']['order'][0]['return_transporter_name'])) {
                $trackingInfo['tracking_code'] = $response['response']['order'][0]['return_transporter_name'];
            }
            return $trackingInfo;
        }

        $this->cronLogger->info('Error: No tracking number found in the response.');
        return $trackingInfo;
    }
}
