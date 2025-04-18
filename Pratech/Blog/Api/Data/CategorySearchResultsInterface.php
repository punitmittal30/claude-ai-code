<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Blog\Api\Data;

interface CategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Category list.
     *
     * @return \Pratech\Blog\Api\Data\CategoryInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     *
     * @param \Pratech\Blog\Api\Data\CategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
