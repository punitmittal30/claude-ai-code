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

namespace Pratech\Search\Api;

interface SearchRepositoryInterface
{
    /**
     * Top Search Terms API
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTopSearchTerms(): array;

    /**
     * Related Search Terms API
     *
     * @param  string $searchTerm
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRelatedSearchTerms(string $searchTerm): array;

    /**
     * Search Term Products API
     *
     * @param  string $searchTerm
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSearchTermProducts(string $searchTerm): array;
}
