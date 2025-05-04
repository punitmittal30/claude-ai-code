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

namespace Pratech\Search\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\Search\Api\SearchRepositoryInterface;
use Pratech\Search\Helper\Search as SearchHelper;

/**
 * Search class to expose search api endpoints.
 */
class Search implements SearchRepositoryInterface
{

    public const SEARCH_API_RESOURCE = 'search';

    /**
     * Search constructor
     *
     * @param SearchHelper $searchHelper
     * @param Response     $response
     */
    public function __construct(
        private SearchHelper                 $searchHelper,
        private Response                                        $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getTopSearchTerms(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::SEARCH_API_RESOURCE,
            $this->searchHelper->getTopSearchTerms()
        );
    }

    /**
     * @inheritDoc
     */
    public function getRelatedSearchTerms(string $searchTerm): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::SEARCH_API_RESOURCE,
            $this->searchHelper->getRelatedSearchTerms($searchTerm)
        );
    }

    /**
     * @inheritDoc
     */
    public function getSearchTermProducts(string $searchTerm): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::SEARCH_API_RESOURCE,
            $this->searchHelper->getSearchTermProducts($searchTerm)
        );
    }
}
