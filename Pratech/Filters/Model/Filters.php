<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\Filters\Api\FiltersRepositoryInterface;
use Pratech\Filters\Helper\Filter as FilterHelper;

/**
 * Filters class to expose filters endpoint
 */
class Filters implements FiltersRepositoryInterface
{
    /**
     * Constant for FILTER API RESOURCE
     */
    public const FILTER_API_RESOURCE = 'filters';

    /**
     * Category Constructor
     *
     * @param Response $response
     * @param FilterHelper $filterHelper
     */
    public function __construct(
        private Response     $response,
        private FilterHelper $filterHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getFiltersPosition(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::FILTER_API_RESOURCE,
            $this->filterHelper->getFiltersPosition()
        );
    }

    /**
     * @inheritDoc
     */
    public function getQuickFilters(int $categoryId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::FILTER_API_RESOURCE,
            $this->filterHelper->getQuickFilters($categoryId)
        );
    }
}
