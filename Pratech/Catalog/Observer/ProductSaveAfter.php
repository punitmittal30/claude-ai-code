<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Observer;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\Full as FullAction;
use Magento\Elasticsearch\Model\Adapter\Elasticsearch as ElasticsearchAdapter;
use Magento\Elasticsearch\Model\Adapter\Index\IndexNameResolver;
use Magento\Elasticsearch\Model\Config;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;

/**
 * Observer to update product index in elasticsearch on product save after event.
 */
class ProductSaveAfter implements ObserverInterface
{
    /**
     * Elasticsearch Client instances
     *
     * @var \Elasticsearch\Client[]
     */
    private $client;

    private const ATTRIBUTES = [
        'PRICE_PER_COUNT' => 'price_per_count',
        'PRICE_PER_100_ML' => 'price_per_100_ml',
        'PRICE_PER_100_GRAM' => 'price_per_100_gram',
        'PRICE_PER_GRAM_PROTEIN' => 'price_per_gram_protein',
    ];
    
    /**
     * @param Batch                  $batch
     * @param Config                 $clientConfig
     * @param FullAction             $fullAction
     * @param IndexNameResolver      $indexNameResolver
     * @param ElasticsearchAdapter   $adapter
     * @param ConnectionManager      $connectionManager
     * @param ScopeResolverInterface $scopeResolver
     * @param Logger                 $apiLogger
     */
    public function __construct(
        private Batch $batch,
        private Config $clientConfig,
        private FullAction $fullAction,
        private IndexNameResolver $indexNameResolver,
        private ElasticsearchAdapter $adapter,
        private ConnectionManager $connectionManager,
        private ScopeResolverInterface $scopeResolver,
        private StoreManagerInterface $storeManager,
        private Logger     $apiLogger
    ) {
        $this->client = $connectionManager->getConnection()->getElasticsearchClient();
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Product $product
        */
        $product = $observer->getEvent()->getData('product');
        $productId = $product->getId();
        try {
            $storeId = 1;
            $this->updateIndex($this->fullAction->rebuildStoreIndex($storeId, [$productId]));
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Update Index
     *
     * @param  \Traversable $documents
     * @return void
     */
    private function updateIndex(\Traversable $documents)
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getId();

        $indexerId = $this->indexNameResolver->getIndexMapping('catalogsearch_fulltext');
        $indexName = $this->indexNameResolver->getIndexName($storeId, $indexerId, []);

        foreach ($this->batch->getItems($documents, 1000) as $documentsBatch) {
            $docs = $this->adapter->prepareDocsPerStore($documentsBatch, $storeId);
            $updateIndexDocuments = $this->getDocsArrayInUpdateIndexFormat($docs, $indexName);
            $this->client->update($updateIndexDocuments);
        }
        
        $this->adapter->updateAlias($storeId, $indexerId);
    }

    /**
     * Reformat documents array to update format
     *
     * @param  array  $documents
     * @param  string $indexName
     * @return array
     */
    protected function getDocsArrayInUpdateIndexFormat(
        $documents,
        $indexName,
    ) {
        $bulkArray = [
            'index' => $indexName,
            'type' => $this->clientConfig->getEntityType(),
            'body' => [],
            'refresh' => true,
        ];

        foreach ($documents as $id => $document) {
            $bulkArray['id'] = $id;
            $bulkArray['body']['doc'] = $document;
        }

        return $bulkArray;
    }
}
