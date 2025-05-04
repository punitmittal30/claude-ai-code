<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Return\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Return\Api\Data\HistoryInterface;
use Pratech\Return\Model\History\ResourceModel\Collection;

interface HistoryRepositoryInterface
{
    /**
     * Create
     *
     * @param  HistoryInterface $history
     * @return HistoryInterface
     * @throws CouldNotSaveException
     */
    public function create(HistoryInterface $history);

    /**
     * Get by id
     *
     * @param  int $eventId
     * @return HistoryInterface
     * @throws NoSuchEntityException
     */
    public function getById($eventId);

    /**
     * Lists
     *
     * @param  SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws NoSuchEntityException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $requestId
     *
     * @return HistoryInterface[]
     */
    public function getRequestEvents($requestId);

    /**
     * @return HistoryInterface
     */
    public function getEmptyEventModel();

    /**
     * @return Collection
     */
    public function getEmptyEventCollection();
}
