<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Filters Interface to expose filters api.
 */
interface FiltersRepositoryInterface
{
    /**
     * Get Filters Position Data
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getFiltersPosition(): array;

    /**
     * Get Quick Filters Data
     *
     * @param  int $categoryId
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getQuickFilters(int $categoryId): array;
}
