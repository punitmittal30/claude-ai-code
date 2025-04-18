<?php
/**
 * Pratech_WarehouseImportExport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\WarehouseImportExport
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\WarehouseImportExport\Model\Import;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;

class WarehouseSla extends AbstractEntity
{
    public const ENTITY_CODE = 'warehouse_sla';
    public const TABLE = 'pratech_warehouse_sla';
    public const ENTITY_ID_COLUMN = 'sla_id';

    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     *
     * @var bool
     */
    protected $logInHistory = true;

    /**
     * Permanent entity columns.
     *
     * @var array
     */
    protected $_permanentAttributes = [
        'sla_id'
    ];

    /**
     * Valid column names allowed
     *
     * @var array
     */
    protected $validColumnNames = [
        'sla_id',
        'customer_pincode',
        'warehouse_pincode',
        'delivery_time',
        'priority'
    ];

    /**
     * @var string[]
     */
    protected $columnsToUpdate = [
        'customer_pincode',
        'warehouse_pincode',
        'delivery_time',
        'priority'
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
     * Courses constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     */
    public function __construct(
        JsonHelper                         $jsonHelper,
        ImportHelper                       $importExportData,
        Data                               $importData,
        ResourceConnection                 $resource,
        Helper                             $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->errorAggregator = $errorAggregator;
        $this->initMessageTemplates();
    }

    /**
     * Init Error Messages
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'PriorityIsRequired',
            __('Priority is required')
        );
        $this->addMessageTemplate(
            'CustomerPincodeIsInvalid',
            __('Customer Pincode must be exactly 6 digits.')
        );
        $this->addMessageTemplate(
            'WarehousePincodeIsInvalid',
            __('Warehouse Pincode must be exactly 6 digits.')
        );
        $this->addMessageTemplate(
            'DeliveryTimeIsRequired',
            __('Delivery Time is required')
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
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->deleteEntity();
                break;
            case Import::BEHAVIOR_APPEND:
            case Import::BEHAVIOR_REPLACE:
                $this->saveAndReplaceEntity();
                break;
        }
        return true;
    }

    /**
     * Delete entities
     *
     * @return void
     */
    private function deleteEntity(): void
    {
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->validateRow($rowData, $rowNum);
                if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    $rowId = $rowData[static::ENTITY_ID_COLUMN];
                    $rows[] = $rowId;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                }
            }
        }
        if ($rows) {
            $this->deleteEntityFinish(array_unique($rows));
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
        $customerPincode = $rowData['customer_pincode'] ?? '';
        $warehousePincode = $rowData['warehouse_pincode'] ?? '';
        $deliveryTime = (int)$rowData['delivery_time'] ?? null;
        $priority = (int)$rowData['priority'] ?? null;
        if ($deliveryTime === null) {
            $this->addRowError('DeliveryTimeIsRequired', $rowNum);
        }
        if ($priority === null) {
            $this->addRowError('PriorityIsRequired', $rowNum);
        }
        if (strlen($customerPincode) != 6) {
            $this->addRowError('CustomerPincodeIsInvalid', $rowNum);
        }
        if (strlen($warehousePincode) != 6) {
            $this->addRowError('WarehousePincodeIsInvalid', $rowNum);
        }
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Delete entities
     *
     * @param array $entityIds
     * @return bool
     */
    private function deleteEntityFinish(array $entityIds): bool
    {
        if ($entityIds) {
            try {
                $this->countItemsDeleted += $this->connection->delete(
                    $this->connection->getTableName(static::TABLE),
                    $this->connection->quoteInto(static::ENTITY_ID_COLUMN . ' IN (?)', $entityIds)
                );
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Save and replace entities
     *
     * @return void
     */
    private function saveAndReplaceEntity(): void
    {
        $behavior = $this->getBehavior();
        $rows = [];
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
                $rows[] = $rowId;
                $columnValues = [];
                foreach ($this->getAvailableColumns() as $columnKey) {
                    $columnValues[$columnKey] = $row[$columnKey];
                }
                $entityList[$rowId][] = $columnValues;
                $this->countItemsCreated += (int)!isset($row[static::ENTITY_ID_COLUMN]);
                $this->countItemsUpdated += (int)isset($row[static::ENTITY_ID_COLUMN]);
            }
            if (Import::BEHAVIOR_REPLACE === $behavior) {
                if ($rows && $this->deleteEntityFinish(array_unique($rows))) {
                    $this->saveEntityFinish($entityList);
                }
            } elseif (Import::BEHAVIOR_APPEND === $behavior) {
                $this->saveEntityFinish($entityList);
            }
        }
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
     * Get columns to update.
     *
     * @return array
     */
    private function getColumnsToUpdate(): array
    {
        return $this->columnsToUpdate;
    }

    /**
     * Save entities
     *
     * @param array $entityData
     * @return void
     */
    private function saveEntityFinish(array $entityData): void
    {
        if ($entityData) {
            $tableName = $this->connection->getTableName(static::TABLE);
            $rows = [];
            foreach ($entityData as $entityRows) {
                foreach ($entityRows as $row) {
                    $rows[] = $row;
                }
            }
            if ($rows) {
                $this->connection->insertOnDuplicate($tableName, $rows, $this->getColumnsToUpdate());
            }
        }
    }
}
