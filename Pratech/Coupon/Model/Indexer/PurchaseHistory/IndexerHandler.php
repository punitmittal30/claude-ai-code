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

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\Search\Request\Dimension;

class IndexerHandler implements IndexerInterface
{
    /**
     * @var bool
     */
    private $isIndexTableExists = false;

    /**
     * Indexer Handler Constructor
     *
     * @param ResourceConnection $resource
     * @param Batch              $batch
     * @param IndexScopeResolver $indexScopeResolver
     * @param IndexStructure     $indexStructure
     * @param array              $data
     * @param integer            $batchSize
     */
    public function __construct(
        private ResourceConnection $resource,
        private Batch $batch,
        private IndexScopeResolver $indexScopeResolver,
        private IndexStructure $indexStructure,
        private array $data,
        private $batchSize = 50000
    ) {
    }

    /**
     * Save Index
     *
     * @param  array        $dimensions
     * @param  \Traversable $documents
     * @return void
     */
    public function saveIndex($dimensions, \Traversable $documents)
    {
        $this->checkIndexTable($dimensions);
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            if (!empty($batchDocuments)) {
                $this->resource->getConnection()
                    ->insertMultiple(
                        $this->getIndexTableName($dimensions),
                        array_values($batchDocuments)
                    );
            }
        }
    }

    /**
     * Delete Index
     *
     * @param  array        $dimensions
     * @param  \Traversable $documents
     * @return void
     */
    public function deleteIndex($dimensions, \Traversable $documents)
    {
        $this->checkIndexTable($dimensions);
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            $this->resource->getConnection()
                ->delete(
                    $this->getIndexTableName($dimensions),
                    [IndexStructure::CUSTOMER_ID . ' in (?)' => $batchDocuments]
                );
        }
    }

    /**
     * Clean Index
     *
     * @param  array $dimensions
     * @return void
     */
    public function cleanIndex($dimensions)
    {
        $this->checkIndexTable($dimensions);
        $this->resource->getConnection()
            ->truncateTable($this->getIndexTableName($dimensions));
    }

    /**
     * Is Available
     *
     * @param  array $dimensions
     * @return boolean
     */
    public function isAvailable($dimensions = []): bool
    {
        return true;
    }

    /**
     * Get Index Name
     *
     * @return string
     */
    private function getIndexName(): string
    {
        return $this->data['indexer_id'];
    }

    /**
     * Get Index Table Name.
     *
     * @param  Dimension[] $dimensions
     * @return string
     */
    private function getIndexTableName(array $dimensions): string
    {
        return $this->indexScopeResolver->resolve($this->getIndexName(), $dimensions);
    }

    /**
     * Check Index Table.
     *
     * @param  Dimension[] $dimensions
     * @return void
     */
    private function checkIndexTable(array $dimensions): void
    {
        if (!$this->isIndexTableExists) {
            $tableName = $this->getIndexTableName($dimensions);
            if (!$this->resource->getConnection()->isTableExists($tableName)) {
                $this->indexStructure->create($this->getIndexName(), [], $dimensions);
            }
            $this->isIndexTableExists = true;
        }
    }
}
