<?php
/**
 * Pratech_SqsIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SqsIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SqsIntegration\Model;

use Enqueue\Sqs\SqsContext;
use Interop\Queue\Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\ConnectionLogger;

/**
 * SQS Event Class to sent data in sqs queue.
 */
class SqsEvent
{
    /**
     * SQS GROUP ID Constant
     */
    public const GROUP_ID = [
        'ORDER_CONFIRMED' => 'hyuga-order-confirmed',
        'PARTIAL_ORDER_CANCELLED' => 'hyuga-partial-order-cancelled',
        'ORDER_CANCELLED' => 'hyuga-order-cancelled',
        'ORDER_SHIPPED' => 'hyuga-order-shipped',
        'ORDER_CLOSED' => 'hyuga-order-closed',
        'ORDER_PENDING' => 'hyuga-order-pending',
        'ORDER_SHIPPED_PARTIAL' => 'hyuga-order-shipped-partial',
        'PAYMENT_FAILED' => 'hyuga-order-payment-failed',
        'INITIATE_REFUND' => 'hyuga-initiate-refund',
        'ORDER_CONFIRMED_FD' => 'hyuga-order-confirmed-fd',
        'ORDER_CANCELLED_FD' => 'hyuga-order-cancelled-fd',
        'HCASH_CREDITED' => 'hyuga-hcash-credited',
        'CLICKPOST_RTO_REFUND' => 'clickpost-rto-refund',
        'RETURN_CREATED' => 'hyuga-return-created',
        'RETURN_APPROVED' => 'hyuga-return-approved',
        'RETURN_OUT_FOR_PICKUP' => 'hyuga-return-out-for-pickup',
        'RETURN_PICKED_UP' => 'hyuga-return-picked-up',
        'RETURN_REJECTED' => 'hyuga-return-rejected',
        'REFUND_INITIATED' => 'hyuga-refund-initiated',
        'REFUND_COMPLETED' => 'hyuga-refund-completed',
        'ORDER_DELIVERED' => 'hyuga-order-delivered',
        'CATALOG_PRODUCT_UPDATED' => 'hyuga-catalog-product-updated',
        'CATALOG_PRODUCT_DELETED' => 'hyuga-catalog-product-deleted',
        'AWB_REGISTERED' => 'hyuga-awb-registered'
    ];

    /**
     * SMS SQS QUEUE Constant
     */
    public const SMS_SQS_QUEUE = "sqs/sqs/sms_queue";

    /**
     * EMAIL SQS QUEUE Constant
     */
    public const EMAIL_SQS_QUEUE = "sqs/sqs/email_queue";

    /**
     * REFUND SQS QUEUE Constant
     */
    public const REFUND_SQS_QUEUE = "sqs/sqs/refund_queue";

    /**
     * CATALOG SQS QUEUE Constant
     */
    public const CATALOG_SQS_QUEUE = "sqs/sqs/catalog_queue";

    /**
     * ENABLE EMAIL COMMUNICATION Constant
     */
    public const ENABLE_EMAIL_COMMUNICATION = "sqs/communication/email";

    /**
     * ENABLE SMS COMMUNICATION Constant
     */
    public const ENABLE_SMS_COMMUNICATION = "sqs/communication/sms";

    /**
     * ENABLE SMS COMMUNICATION Constant
     */
    public const ENABLE_AUTO_REFUND = "sqs/communication/refund";

    /**
     * ENABLE CATALOG COMMUNICATION Constant
     */
    public const ENABLE_CATALOG = "sqs/communication/catalog";

    /**
     * @var SqsContext|null
     */
    protected ?SqsContext $sqsContext;

    /**
     * SQS Event Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConnectionLogger $connectionLogger
     * @param SqsConnection $sqsConnection
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ConnectionLogger     $connectionLogger,
        SqsConnection                $sqsConnection,
    ) {
        $this->sqsContext = $sqsConnection->connect()->createContext();
    }

    /**
     * Send Sms Event To SQS
     *
     * @param array $data
     * @return void
     */
    public function sentSmsEventToSqs(array $data): void
    {
        $isSmsEnabled = (int)$this->getConfigValue(self::ENABLE_SMS_COMMUNICATION);
        if ($this->sqsContext) {
            $queueName = $this->getConfigValue(self::SMS_SQS_QUEUE);
            if (!empty($queueName) && $isSmsEnabled) {
                $this->sendEventToSqs($data, $queueName);
            }
        }
    }

    /**
     * Get System Config Value
     *
     * @param string $path
     * @return mixed
     */
    private function getConfigValue(string $path): mixed
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Send Event To SQS.
     *
     * @param array $data
     * @param string $queueName
     * @return void
     */
    protected function sendEventToSqs(array $data, string $queueName): void
    {
        try {
            $eventName = $data['event_name'];
            $groupId = self::GROUP_ID[$eventName];

            $queue = $this->sqsContext->createQueue($queueName);
            $queue->setFifoQueue(true);
            $queue->setContentBasedDeduplication(true);

            $message = $this->sqsContext->createMessage(json_encode($data));
            $message->setMessageGroupId($groupId);

            $this->sqsContext->createProducer()->send($queue, $message);
        } catch (\Exception $exception) {
            $this->connectionLogger->error($exception->getMessage() . __METHOD__);
        } catch (Exception $e) {
            $this->connectionLogger->error($e->getMessage() . __METHOD__);
        }
    }

    /**
     * Send Email Event To SQS
     *
     * @param array $data
     * @return void
     */
    public function sentEmailEventToSqs(array $data): void
    {
        $isEmailEnabled = (int)$this->getConfigValue(self::ENABLE_EMAIL_COMMUNICATION);
        if ($this->sqsContext) {
            $queueName = $this->getConfigValue(self::EMAIL_SQS_QUEUE);
            if (!empty($queueName) && $isEmailEnabled) {
                $this->sendEventToSqs($data, $queueName);
            }
        }
    }

    /**
     * Initiate Refund On SQS.
     *
     * @param array $data
     * @param string $eventName
     * @return void
     */
    public function initiateRefundOnSqs(array $data, string $eventName): void
    {
        $payload = [
            'type' => 'refund',
            'event_name' => $eventName,
            'data' => $data
        ];

        $isAutoRefund = (int)$this->getConfigValue(self::ENABLE_AUTO_REFUND);
        if ($this->sqsContext) {
            $queueName = $this->getConfigValue(self::REFUND_SQS_QUEUE);
            if (!empty($queueName) && $isAutoRefund) {
                $this->sendEventToSqs($payload, $queueName);
            }
        }
    }

    /**
     * Store Credit Balance Credited Event On SQS.
     *
     * @param array $data
     * @return void
     */
    public function sendStoreCreditEvent(array $data): void
    {
        $payload = [
            'type' => 'event',
            'event_name' => 'HCASH_CREDITED',
            'data' => $data
        ];

        if ($this->sqsContext) {
            $queueName = $this->getConfigValue(self::SMS_SQS_QUEUE);
            if (!empty($queueName)) {
                $this->sendEventToSqs($payload, $queueName);
            }
        }
    }

    /**
     * Catalog Event On SQS.
     *
     * @param array $data
     * @param string $eventName
     * @return void
     */
    public function sendCatalogEvent(array $data, string $eventName): void
    {
        $payload = [
            'type' => 'catalog',
            'event_name' => $eventName,
            'data' => $data
        ];

        $isCatalogEnabled = (int)$this->getConfigValue(self::ENABLE_CATALOG);
        if ($this->sqsContext) {
            $queueName = $this->getConfigValue(self::CATALOG_SQS_QUEUE);
            if (!empty($queueName) && $isCatalogEnabled) {
                $this->sendEventToSqs($payload, $queueName);
            }
        }
    }
}
