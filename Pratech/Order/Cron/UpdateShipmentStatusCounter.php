<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory as StatusCollectionFactory;

/**
 * Cron to initialize the shipment status counter for customers.
 */
class UpdateShipmentStatusCounter
{

    /**
     * IS CRON ENABLED FOR UPDATE SHIPMENT STATUS COUNTER
     */
    public const IS_CRON_ENABLED = 'cron_schedule/shipment_status_counter/status';

    /**
     * MODE FOR UPDATE SHIPMENT STATUS COUNTER
     */
    public const COUNTER_MODE = 'cron_schedule/shipment_status_counter/mode';

    /**
     * Update Shipment Status Counter Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resource
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param CronLogger $cronLogger
     * @param StatusCollectionFactory $statusCollectionFactory
     */
    public function __construct(
        private ScopeConfigInterface        $scopeConfig,
        private ResourceConnection          $resource,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerCollectionFactory   $customerCollectionFactory,
        private ShipmentRepositoryInterface $shipmentRepository,
        private ShipmentCollectionFactory   $shipmentCollectionFactory,
        private CronLogger                  $cronLogger,
        private StatusCollectionFactory     $statusCollectionFactory
    ) {
    }

    /**
     * Execute function to update shipment status counters for customer.
     *
     * @return bool
     */
    public function execute()
    {
        if (!$this->scopeConfig->getValue(self::IS_CRON_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return true;
        }
        $counterMode = $this->scopeConfig->getValue(self::COUNTER_MODE, ScopeInterface::SCOPE_STORE);
        $this->cronLogger->info(
            $counterMode . ' UpdateShipmentStatusCounter cron started at ' . date('Y-m-d H:i:s')
        );
        try {
            $deliveredStatusId = $rtoStatusId = 0;
            $requiredStatusIds = [];
            $statusCollection = $this->statusCollectionFactory->create();
            foreach ($statusCollection as $status) {
                if ($status->getStatusCode() == 'delivered') {
                    $deliveredStatusId = $status->getStatusId();
                    $requiredStatusIds[] = $status->getStatusId();
                } elseif ($status->getStatusCode() == 'rto') {
                    $rtoStatusId = $status->getStatusId();
                    $requiredStatusIds[] = $status->getStatusId();
                }
            }
            if (empty($requiredStatusIds)) {
                return true;
            }
            if ($counterMode == 'initial') {
                $customerIds = $this->shipmentCollectionFactory->create()
                    ->addFieldToSelect('customer_id')
                    ->addFieldToFilter('shipment_status', ['in' => $requiredStatusIds])
                    ->addFieldToFilter('is_counted', ['null' => true])
                    ->distinct(true)
                    ->getColumnValues('customer_id');
                foreach ($customerIds as $customerId) {
                    try {
                        $totalDeliveredShipments = $totalRtoShipments = 0;
                        $customerShipments = $this->shipmentCollectionFactory->create()
                            ->addFieldToSelect(['entity_id', 'shipment_status', 'is_counted'])
                            ->addFieldToFilter('customer_id', $customerId)
                            ->addFieldToFilter('shipment_status', ['in' => $requiredStatusIds]);
                        foreach ($customerShipments as $shipment) {
                            if ($shipment->getShipmentStatus() == $deliveredStatusId) {
                                $totalDeliveredShipments++;
                            } elseif ($shipment->getShipmentStatus() == $rtoStatusId) {
                                $totalRtoShipments++;
                            }
                            if (!$shipment->getIsCounted()) {
                                $shipmentModel = $this->shipmentRepository->get($shipment->getId());
                                $shipmentModel->setIsCounted(true);
                                $this->shipmentRepository->save($shipmentModel);
                            }
                        }
                        $customerData = $this->customerRepository->getById($customerId);
                        $customerData->setCustomAttribute('total_delivered_shipments', $totalDeliveredShipments);
                        $customerData->setCustomAttribute('total_rto_shipments', $totalRtoShipments);
                        $this->customerRepository->save($customerData);
                    } catch (Exception $exception) {
                        $this->cronLogger->error('Customer ID: ' . $customerId
                            . ' Error: ' . $exception->getMessage() . __METHOD__);
                    }
                }
            } elseif ($counterMode == 'update') {
                $shipments = $this->shipmentCollectionFactory->create()
                    ->addFieldToSelect(['entity_id', 'customer_id', 'shipment_status'])
                    ->addFieldToFilter('shipment_status', ['in' => $requiredStatusIds])
                    ->addFieldToFilter('is_counted', ['null' => true]);
                $countedShipmentIds = [];
                foreach ($shipments as $shipment) {
                    try {
                        $customerId = $shipment->getCustomerId();
                        $customerData = $this->customerRepository->getById($customerId);
                        if ($shipment->getShipmentStatus() == $deliveredStatusId) {
                            $totalDeliveredShipments = $customerData->getCustomAttribute('total_delivered_shipments')
                                ? $customerData->getCustomAttribute('total_delivered_shipments')->getValue()
                                : 0;
                            $totalDeliveredShipments = $totalDeliveredShipments ? ($totalDeliveredShipments + 1) : 1;
                            $customerData->setCustomAttribute('total_delivered_shipments', $totalDeliveredShipments);
                        } elseif ($shipment->getShipmentStatus() == $rtoStatusId) {
                            $totalRtoShipments = $customerData->getCustomAttribute('total_rto_shipments')
                                ? $customerData->getCustomAttribute('total_rto_shipments')->getValue()
                                : 0;
                            $totalRtoShipments = $totalRtoShipments ? ($totalRtoShipments + 1) : 1;
                            $customerData->setCustomAttribute('total_rto_shipments', $totalRtoShipments);
                        }
                        $this->customerRepository->save($customerData);
                        $countedShipmentIds[] = $shipment->getId();
                    } catch (Exception $exception) {
                        $this->cronLogger->error('Shipment ID: ' . $shipment->getId()
                            . ' Error: ' . $exception->getMessage() . __METHOD__);
                    }
                }
                if (!empty($countedShipmentIds)) {
                    $countedShipmentIdsString = implode(",", $countedShipmentIds);
                    $this->setShipmentTableRecords(
                        "`entity_id` IN (" . $countedShipmentIdsString . ")",
                        ['is_counted' => true]
                    );
                }
            }
        } catch (Exception $exception) {
            $this->cronLogger->error($exception->getMessage() . __METHOD__);
        }
        $this->cronLogger->info(
            $counterMode . ' UpdateShipmentStatusCounter cron ended at ' . date('Y-m-d H:i:s')
        );
    }

    /**
     * Set Shipment Table Records
     *
     * @param string $condition
     * @param array $columnData
     * @return void
     */
    public function setShipmentTableRecords($condition, $columnData)
    {
        $connection = $this->resource->getConnection();
        $tableName = $connection->getTableName('sales_shipment');
        return $connection->update($tableName, $columnData, $condition);
    }
}
