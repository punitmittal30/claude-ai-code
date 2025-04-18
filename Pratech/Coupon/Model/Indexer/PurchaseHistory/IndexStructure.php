<?php
/**
 * Pratech_Coupon
 *
 * @category  XML
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\Coupon\Model\Indexer\PurchaseHistory;

use Pratech\Coupon\Model\Indexer\PurchaseHistory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure implements IndexStructureInterface
{
    public const ID_FIELD = 'row_id';
    public const CUSTOMER_ID = 'customer_id';
    public const APP_ORDERS_COUNT = 'app_orders_count';
    public const WEB_ORDERS_COUNT = 'web_orders_count';

    /**
     * @var array
     */
    private $fields = [
        self::CUSTOMER_ID => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],
        self::APP_ORDERS_COUNT => [
            'type' => Table::TYPE_SMALLINT,
            'size' => 5
        ],
        self::WEB_ORDERS_COUNT => [
            'type' => Table::TYPE_SMALLINT,
            'size' => 5
        ],
    ];

    /**
     * @param ResourceConnection $resource
     * @param IndexScopeResolver $indexScopeResolver
     */
    public function __construct(
        private ResourceConnection $resource,
        private IndexScopeResolver $indexScopeResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function delete($index, array $dimensions = [])
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->indexScopeResolver->resolve($index, $dimensions);
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    /**
     * @inheritDoc
     */
    public function create($index, array $fields, array $dimensions = [])
    {
        $connection = $this->resource->getConnection();
        $ddlTable = $connection->newTable($this->indexScopeResolver->resolve($index, $dimensions));
        $ddlTable->addColumn(
            self::ID_FIELD,
            Table::TYPE_BIGINT,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ],
            'Index Row Id'
        );

        $fields = array_merge($this->fields, $fields);
        foreach ($fields as $fieldName => $fieldDefinition) {
            $columnOptions = [];
            if ($fieldName == self::CUSTOMER_ID) {
                $columnOptions = [
                    'unsigned' => true,
                    'nullable' => false
                ];
            }

            $ddlTable->addColumn(
                $fieldName,
                $fieldDefinition['type'] ?? Table::TYPE_TEXT,
                $fieldDefinition['size'] ?? 255,
                $columnOptions
            );
        }

        $ddlTable->addForeignKey(
            $this->resource->getFkName(
                PurchaseHistory::INDEXER_ID,
                self::CUSTOMER_ID,
                'customer_entity',
                'entity_id'
            ),
            self::CUSTOMER_ID,
            $this->resource->getTableName('customer_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'Pratech Purchase History Index'
        );

        $connection->createTable($ddlTable);
    }
}
