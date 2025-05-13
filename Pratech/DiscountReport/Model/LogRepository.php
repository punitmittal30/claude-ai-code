<?php
/**
 * Pratech_DiscountReport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\DiscountReport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\DiscountReport\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\DiscountReport\Api\Data\LogInterface;
use Pratech\DiscountReport\Api\Data\LogInterfaceFactory;
use Pratech\DiscountReport\Api\Data\LogSearchResultsInterfaceFactory;
use Pratech\DiscountReport\Api\LogRepositoryInterface;
use Pratech\DiscountReport\Model\ResourceModel\Log as ResourceLog;
use Pratech\DiscountReport\Model\ResourceModel\Log\CollectionFactory as LogCollectionFactory;

class LogRepository implements LogRepositoryInterface
{

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var LogInterfaceFactory
     */
    protected $logFactory;

    /**
     * @var LogCollectionFactory
     */
    protected $logCollectionFactory;

    /**
     * @var ResourceLog
     */
    protected $resource;

    /**
     * @var Log
     */
    protected $searchResultsFactory;

    /**
     * @param ResourceLog $resource
     * @param LogInterfaceFactory $logFactory
     * @param LogCollectionFactory $logCollectionFactory
     * @param LogSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceLog                      $resource,
        LogInterfaceFactory              $logFactory,
        LogCollectionFactory             $logCollectionFactory,
        LogSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface     $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->logFactory = $logFactory;
        $this->logCollectionFactory = $logCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(LogInterface $log)
    {
        try {
            $this->resource->save($log);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the log: %1',
                $exception->getMessage()
            ));
        }
        return $log;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->logCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($logId)
    {
        return $this->delete($this->get($logId));
    }

    /**
     * @inheritDoc
     */
    public function delete(LogInterface $log)
    {
        try {
            $logModel = $this->logFactory->create();
            $this->resource->load($logModel, $log->getLogId());
            $this->resource->delete($logModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Log: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get($logId)
    {
        $log = $this->logFactory->create();
        $this->resource->load($log, $logId);
        if (!$log->getId()) {
            throw new NoSuchEntityException(__('Log with id "%1" does not exist.', $logId));
        }
        return $log;
    }
}
