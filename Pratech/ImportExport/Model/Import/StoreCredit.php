<?php

namespace Pratech\ImportExport\Model\Import;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Pratech\Base\Logger\Logger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

/**
 * Store Credit Import Model
 */
class StoreCredit extends AbstractEntity
{
    public const ENTITY_CODE = 'store_credit';

    public const TABLE = 'magento_customerbalance_history';
    public const ENTITY_ID_COLUMN = 'customer_id';

    /**
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * @var bool
     */
    protected $logInHistory = true;

    /**
     * @var string[]
     */
    protected $_permanentAttributes = [
        'customer_id'
    ];

    /**
     * @var string[]
     */
    protected $validColumnNames = [
        'customer_id',
        'amount',
        'comment',
        'is_refund',
        'order_id',
        'expiry_days'
    ];

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerRedisCache
     */
    private $customerRedisCache;

    /**
     * @var Logger
     */
    private $apiLogger;

    /**
     * @var StoreCreditHelper
     */
    private $storeCreditHelper;

    /**
     * Courses constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param BalanceFactory $balanceFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerRedisCache $customerRedisCache
     * @param Logger $apiLogger
     * @param StoreCreditHelper $storeCreditHelper
     * @param SalesOrderFactory $salesOrderFactory
     */
    public function __construct(
        JsonHelper                         $jsonHelper,
        ImportHelper                       $importExportData,
        Data                               $importData,
        ResourceConnection                 $resource,
        Helper                             $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        BalanceFactory                     $balanceFactory,
        CustomerRepositoryInterface        $customerRepository,
        CustomerRedisCache                 $customerRedisCache,
        Logger                             $apiLogger,
        StoreCreditHelper                  $storeCreditHelper,
        private SalesOrderFactory          $salesOrderFactory
    )
    {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->balanceFactory = $balanceFactory;
        $this->customerRepository = $customerRepository;
        $this->customerRedisCache = $customerRedisCache;
        $this->apiLogger = $apiLogger;
        $this->storeCreditHelper = $storeCreditHelper;
        $this->initMessageTemplates();
    }

    /**
     * Init Error Messages
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'CustomerIdIsInvalid',
            __('Customer Id is invalid')
        );
        $this->addMessageTemplate(
            'CustomerIdIsRequired',
            __('Customer Id cannot be empty')
        );
        $this->addMessageTemplate(
            'AmountIsRequired',
            __('Amount should be greater than 0')
        );
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode(): string
    {
        return static::ENTITY_CODE;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Import data
     *
     * @return bool
     * @throws Exception
     */
    protected function _importData(): bool
    {
        if (in_array($this->getBehavior(), [Import::BEHAVIOR_APPEND, Import::BEHAVIOR_DELETE])) {
            $this->saveAndDeleteEntity();
        }
        return true;
    }

    /**
     * Save and replace entities
     *
     * @return void
     */
    private function saveAndDeleteEntity(): void
    {
        $behavior = $this->getBehavior();
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityList = [];

            foreach ($bunch as $rowNum => $row) {
                if (!$this->validateRow($row, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                $rowId = $row[static::ENTITY_ID_COLUMN];
                $columnValues = [];

                foreach ($this->getAvailableColumns() as $columnKey) {
                    $columnValues[$columnKey] = $row[$columnKey];
                }

                $entityList[$rowId][] = $columnValues;
                if (Import::BEHAVIOR_APPEND === $behavior) {
                    $this->countItemsCreated += (int)!isset($row[static::ENTITY_ID_COLUMN]);
                    $this->countItemsUpdated += (int)isset($row[static::ENTITY_ID_COLUMN]);
                } elseif (Import::BEHAVIOR_DELETE === $behavior) {
                    $this->countItemsDeleted += (int)isset($row[static::ENTITY_ID_COLUMN]);
                }
            }

            if (Import::BEHAVIOR_APPEND === $behavior) {
                $this->saveEntityFinish($entityList);
            } elseif (Import::BEHAVIOR_DELETE === $behavior) {
                $this->deleteEntityFinish($entityList);
            }
        }
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        $behavior = $this->getBehavior();
        $customerId = $rowData['customer_id'] ?? '';
        $amount = (int)$rowData['amount'] ?? 1;
        $isRefund = (int)$rowData['is_refund'] ?? 0;
        $orderId = $rowData['order_id'] ?? '';
        try {
            $isCustomerExist = $this->customerRepository->getById($customerId);
        } catch (Exception $e) {
            $isCustomerExist = 0;
        }

        try {
            $isOrderExist = $this->salesOrderFactory->create()
                ->loadByIncrementId($orderId);
        } catch (Exception $e) {
            $isOrderExist = 0;
        }

        if (!$isCustomerExist) {
            $this->addRowError('CustomerIdIsInvalid', $rowNum);
        }

        if (!$customerId) {
            $this->addRowError('CustomerIdIsRequired', $rowNum);
        }

        if (!$amount && Import::BEHAVIOR_DELETE !== $behavior) {
            $this->addRowError('AmountIsRequired', $rowNum);
        }

        if ($isRefund && !$isOrderExist) {
            $this->addRowError('OrderIdIsInvalid', $rowNum);
        }

        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Save entities
     *
     * @param array $entityData
     * @return bool
     */
    private function saveEntityFinish(array $entityData): bool
    {
        if ($entityData) {
            foreach ($entityData as $entityRows) {
                foreach ($entityRows as $row) {
                    if (!$row['is_refund']) {
                        $this->storeCreditHelper->addStoreCredit(
                            $row['customer_id'],
                            $row['amount'],
                            $row['comment'],
                            [
                                'event_name' => 'import'
                            ],
                            $row['expiry_days']
                        );
                    } else {
                        $this->storeCreditHelper->refundStoreCredit(
                            $row['customer_id'],
                            $row['amount'],
                            $row['order_id']
                        );
                    }

                    try {
                        $this->customerRedisCache->deleteCustomerStoreCreditTransactions($row['customer_id']);
                    } catch (Exception $exception) {
                        $this->apiLogger->error($exception->getMessage() . __METHOD__);
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Delete entities
     *
     * @param array $entityData
     * @return bool
     */
    private function deleteEntityFinish(array $entityData): bool
    {
        if ($entityData) {
            foreach ($entityData as $entityRows) {
                foreach ($entityRows as $row) {
                    try {
                        $this->storeCreditHelper->deletestoreCredit(
                            $row['customer_id']
                        );
                        $this->customerRedisCache->deleteCustomerStoreCreditTransactions($row['customer_id']);
                    } catch (Exception $exception) {
                        $this->apiLogger->error($exception->getMessage() . __METHOD__);
                    }
                }
            }
            return true;
        }
        return false;
    }
}
