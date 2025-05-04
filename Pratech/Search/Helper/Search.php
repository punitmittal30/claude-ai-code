<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Helper;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Helper\Product as ProductHelper;
use Pratech\Search\Model\SearchTermsFactory;

/**
 * Search helper class to provide data to search api endpoints.
 */
class Search
{
    /**
     * TOP TERMS Constant
     */
    public const TOP_TERMS = 'search/search/top_terms';

    /**
     * Search Constructor
     *
     * @param Logger                     $logger
     * @param ScopeConfigInterface       $scopeConfig
     * @param QueryFactory               $queryFactory
     * @param SearchTermsFactory         $searchTermsFactory
     * @param ProductHelper              $productHelper
     * @param ProductRepositoryInterface $productRepository,
     */
    public function __construct(
        private Logger                               $logger,
        private ScopeConfigInterface $scopeConfig,
        private QueryFactory                         $queryFactory,
        private SearchTermsFactory $searchTermsFactory,
        private ProductHelper $productHelper,
        private ProductRepositoryInterface           $productRepository,
    ) {
    }

    /**
     * Top Search Terms API
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getTopSearchTerms(): array
    {
        try {
            $topTerms = $this->scopeConfig->getValue(self::TOP_TERMS, ScopeInterface::SCOPE_STORE);
            if ($topTerms) {
                return explode(',', $topTerms);
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }

        return [];
    }

    /**
     * Top Search Terms API
     *
     * @param  string $searchTerm
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRelatedSearchTerms(string $searchTerm): array
    {
        try {
            $searchTerms = [];
            $searchTermsCollection = $this->queryFactory->create()
                ->getCollection()
                ->addFieldToFilter('num_results', ['gt' => 0])
                ->addFieldToFilter('query_text', ['like' => '%'.$searchTerm.'%'])
                ->setOrder('popularity', 'DSC')
                ->setPageSize(5);
            foreach ($searchTermsCollection as $searchTerm) {
                $searchTerms[] = [
                    'text' => $searchTerm->getQueryText(),
                    'uses' => $searchTerm->getPopularity(),
                    'results' => $searchTerm->getNumResults(),
                ];
            }
            return $searchTerms;
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }

        return [];
    }

    /**
     * Top Search Terms API
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSearchTermProducts(string $searchTerm): array
    {
        try {
            $searchTerms = [];
            $searchTermsCollection = $this->searchTermsFactory->create()
                ->getCollection()
                ->addFieldToFilter('product_ids', ['notnull' => true])
                ->addFieldToFilter('keyword', ['like' => '%'.$searchTerm.'%'])
                ->setPageSize(5);
            foreach ($searchTermsCollection as $searchTerm) {
                $productIds = explode(',', $searchTerm->getProductIds());
                $searchTerms[] = [
                    'text' => $searchTerm->getKeyword(),
                    'products' => $this->getProductResponse($productIds)
                ];
            }
            return $searchTerms;
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }

        return [];
    }

    /**
     * Get Product Response by Id
     *
     * @param  array $productIds
     * @return array
     */
    private function getProductResponse(array $productIds): array
    {
        $productsData = [];
        foreach ($productIds as $productId) {
            try {
                $product = $this->productRepository->getById($productId);
                $productStock = $this->productHelper->getProductStockInfo($productId);
                if ($product->getStatus() == 1 && $productStock->getIsInStock()) {
                    $productsData[] = [
                        "id" => $product->getId(),
                        "sku" => $product->getSku(),
                        "name" => $product->getName(),
                        "image" => $product->getImage(),
                        "slug" => $product->getUrlKey()
                    ];
                }
            } catch (Exception $exception) {
                continue;
            }
        }
        return $productsData;
    }
}
