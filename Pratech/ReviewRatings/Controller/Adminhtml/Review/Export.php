<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Controller\Adminhtml\Review;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

class Export extends Action
{
    /**
     * Header Constant
     */
    public const HEADER = [
        'review_id',
        'nickname',
        'title',
        'detail',
        'rating_value',
        'sku',
        'status'
    ];

    /**
     * Page Size Config Path
     */
    public const PAGE_SIZE_PATH = 'review/export/page_size';

    /**
     * @var array
     */
    protected $reviews = [];

    /**
     * @var string
     */
    protected $pageSize;

    /**
     * Constructor
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param CollectionFactory $collectionFactory
     * @param RedirectInterface $redirect
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        protected Context                  $context,
        protected FileFactory              $fileFactory,
        protected CollectionFactory        $collectionFactory,
        protected RedirectInterface        $redirect,
        private ScopeConfigInterface       $scopeConfig,
        private ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResponseInterface
    {
        try {
            $params = [];
            $filter = $this->getRequest()->getParam('filter');
            if ($filter) {
                parse_str(base64_decode($filter), $params);
            }

            // Create a collection of reviews
            $collection = $this->collectionFactory->create();

            $collection->getSelect()->join(
                ['rating' => $collection->getTable('rating_option_vote')],
                'main_table.review_id = rating.review_id',
                ['rating_value' => 'rating.value']
            );

            $collection->getSelect()->join(
                ['product' => $collection->getTable('catalog_product_entity')],
                'main_table.entity_pk_value = product.entity_id',
                ['sku_code' => 'product.sku']
            );

            foreach ($params as $field => $value) {
                switch ($field) {
                    case 'review_id':
                        $collection->addFieldToFilter('main_table.' . $field, $value);
                        break;
                    case 'created_at':
                        $fromDate = isset($value['from']) ? date('Y-m-d', strtotime($value['from'])) : null;
                        $toDate = isset($value['to']) ? date('Y-m-d', strtotime($value['to'] . ' +1 day')) : null;
                        if ($fromDate || $toDate) {
                            $collection->addFieldToFilter('main_table.created_at', ['from' => $fromDate, 'to' => $toDate]);
                        }
                        break;
                    case 'status':
                        $collection->addStatusFilter((int)$value);
                        break;
                    case 'rating':
                        $collection->addFieldToFilter('rating.value', $value);
                        break;
                    case 'sku':
                        $product = $this->productRepository->get($value);
                        $collection->addFieldToFilter('main_table.entity_pk_value', $product->getId());
                        break;
                    case 'title':
                    case 'detail':
                    case 'nickname':
                        $collection->addFieldToFilter($field, ['like' => '%' . $value . '%']);
                        break;
                    case 'name':
                    case 'visible_in':
                        break;
                    default:
                        $collection->addFieldToFilter($field, $value);
                }

            }
            $this->pageSize = $this->scopeConfig->getValue(
                self::PAGE_SIZE_PATH,
                ScopeInterface::SCOPE_STORE
            );
            $selectIds = $collection->getAllIds();
            $collection->setPageSize($this->pageSize);
            $pageCount = $collection->getLastPageNumber();

            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $this->reviews = array_merge($this->reviews, $this->loadReviewData($selectIds, $pageNum));
            }
            $output = array_merge([self::HEADER], array_values($this->reviews));

            $fileContent = $this->fileFactory->create(
                'reviews.csv',
                $this->getCsvContent($output),
                DirectoryList::VAR_DIR,
                'application/octet-stream'
            );

            return $fileContent;
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }

    /**
     * Load Review Data
     *
     * @param array $selectIds
     * @param int $pageNum
     * @return array
     */
    private function loadReviewData(array $selectIds, int $pageNum = 0): array
    {
        $result = [];
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->join(
            ['rating' => $collection->getTable('rating_option_vote')],
            'main_table.review_id = rating.review_id',
            ['rating_value' => 'rating.value']
        )->join(
            ['product' => $collection->getTable('catalog_product_entity')],
            'main_table.entity_pk_value = product.entity_id',
            ['sku_code' => 'product.sku']
        )->where(
            'main_table.review_id IN (?)',
            $selectIds
        );
        $collection->setPageSize($this->pageSize);
        if ($pageNum > 0) {
            $collection->setCurPage($pageNum);
        }

        foreach ($collection as $review) {
            $result[] = [
                $review->getReviewId(),
                $review->getNickname(),
                $review->getTitle(),
                $review->getDetail(),
                $review->getRatingValue(),
                $review->getSkuCode(),
                $review->getStatusId()
            ];
        }
        return $result;
    }

    /**
     * Get Csv Content
     *
     * @param array $data
     * @return string
     */
    protected function getCsvContent(array $data): string
    {
        $csvContent = '';
        foreach ($data as $row) {
            $csvContent .= implode(',', array_map([$this, 'encloseCsvField'], $row)) . "\n";
        }
        return $csvContent;
    }

    /**
     * Enclose Csv Field
     *
     * @param string $field
     * @return string
     */
    protected function encloseCsvField(string $field): string
    {
        // If the field contains a comma, double-quote, or newline, enclose it in double quotes
        if (preg_match('/[",\n]/', $field)) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
}
