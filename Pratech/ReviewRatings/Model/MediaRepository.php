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
use Pratech\ReviewRatings\Api\Data\MediaInterface;
use Pratech\ReviewRatings\Api\Data\MediaInterfaceFactory;
use Pratech\ReviewRatings\Api\Data\MediaSearchResultsInterfaceFactory;
use Pratech\ReviewRatings\Api\MediaRepositoryInterface;
use Pratech\ReviewRatings\Model\ResourceModel\Media as ResourceMedia;
use Pratech\ReviewRatings\Model\ResourceModel\Media\CollectionFactory as MediaCollectionFactory;

class MediaRepository implements MediaRepositoryInterface
{

    /**
     * @var Media
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ResourceMedia
     */
    protected $resource;

    /**
     * @var MediaCollectionFactory
     */
    protected $mediaCollectionFactory;

    /**
     * @var MediaInterfaceFactory
     */
    protected $mediaFactory;

    /**
     * @param ResourceMedia $resource
     * @param MediaInterfaceFactory $mediaFactory
     * @param MediaCollectionFactory $mediaCollectionFactory
     * @param MediaSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceMedia $resource,
        MediaInterfaceFactory $mediaFactory,
        MediaCollectionFactory $mediaCollectionFactory,
        MediaSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->mediaFactory = $mediaFactory;
        $this->mediaCollectionFactory = $mediaCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(MediaInterface $media)
    {
        try {
            $this->resource->save($media);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the media: %1',
                $exception->getMessage()
            ));
        }
        return $media;
    }

    /**
     * @inheritDoc
     */
    public function get($mediaId)
    {
        $media = $this->mediaFactory->create();
        $this->resource->load($media, $mediaId);
        if (!$media->getId()) {
            throw new NoSuchEntityException(__('Media with id "%1" does not exist.', $mediaId));
        }
        return $media;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->mediaCollectionFactory->create();

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
    public function delete(MediaInterface $media)
    {
        try {
            $mediaModel = $this->mediaFactory->create();
            $this->resource->load($mediaModel, $media->getMediaId());
            $this->resource->delete($mediaModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Media: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($mediaId)
    {
        return $this->delete($this->get($mediaId));
    }
}
