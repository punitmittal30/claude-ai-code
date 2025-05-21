<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\ReviewRatings\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\ReviewRatings\Api\Data\KeywordsInterface;
use Pratech\ReviewRatings\Api\Data\KeywordsInterfaceFactory;
use Pratech\ReviewRatings\Api\Data\KeywordsSearchResultsInterfaceFactory;
use Pratech\ReviewRatings\Api\KeywordsRepositoryInterface;
use Pratech\ReviewRatings\Model\ResourceModel\Keywords as ResourceKeywords;
use Pratech\ReviewRatings\Model\ResourceModel\Keywords\CollectionFactory as KeywordsCollectionFactory;

class KeywordsRepository implements KeywordsRepositoryInterface
{

    /**
     * @param ResourceKeywords $resource
     * @param KeywordsInterfaceFactory $keywordsFactory
     * @param KeywordsCollectionFactory $keywordsCollectionFactory
     * @param KeywordsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        protected ResourceKeywords $resource,
        protected KeywordsInterfaceFactory $keywordsFactory,
        protected KeywordsCollectionFactory $keywordsCollectionFactory,
        protected KeywordsSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(KeywordsInterface $keywords)
    {
        try {
            $this->resource->save($keywords);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the keywords: %1',
                $exception->getMessage()
            ));
        }
        return $keywords;
    }

    /**
     * @inheritDoc
     */
    public function get($keywordsId)
    {
        $keywords = $this->keywordsFactory->create();
        $this->resource->load($keywords, $keywordsId);
        if (!$keywords->getId()) {
            throw new NoSuchEntityException(__('Keywords with id "%1" does not exist.', $keywordsId));
        }
        return $keywords;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->keywordsCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
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
    public function delete(KeywordsInterface $keywords)
    {
        try {
            $keywordsModel = $this->keywordsFactory->create();
            $this->resource->load($keywordsModel, $keywords->getKeywordsId());
            $this->resource->delete($keywordsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Keywords: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($keywordsId)
    {
        return $this->delete($this->get($keywordsId));
    }
}
