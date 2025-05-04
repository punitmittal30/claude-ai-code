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

namespace Pratech\Blog\Model;

use Exception;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as CmsPagesCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Model\Data\Response;
use Pratech\Blog\Api\Data\TagInterface;
use Pratech\Blog\Api\Data\TagInterfaceFactory;
use Pratech\Blog\Api\Data\TagSearchResultsInterfaceFactory;
use Pratech\Blog\Api\TagRepositoryInterface;
use Pratech\Blog\Model\ResourceModel\Tag as ResourceTag;
use Pratech\Blog\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use Pratech\CmsBlock\Model\Author;
use Pratech\CmsBlock\Model\AuthorFactory;

class TagRepository implements TagRepositoryInterface
{
    /**
     * BLOGS API RESOURCE
     */
    public const BLOGS_API_RESOURCE = "blogs";

    /**
     * SUCCESS CODE 200
     */
    public const SUCCESS_CODE = 200;

    /**
     * IMAGE LOCATION CONSTANT
     */
    public const IMAGE_LOCATION = 'cms/image';

    /**
     * @var ResourceTag
     */
    protected $resource;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var TagCollectionFactory
     */
    protected $tagCollectionFactory;

    /**
     * @var TagInterfaceFactory
     */
    protected $tagFactory;

    /**
     * @var Tag
     */
    protected $searchResultsFactory;

    /**
     * @param ResourceTag $resource
     * @param TagInterfaceFactory $tagFactory
     * @param TagCollectionFactory $tagCollectionFactory
     * @param TagSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CmsPagesCollectionFactory $cmsPagesCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Response $response
     * @param Logger $apiLogger
     * @param AuthorFactory $authorFactory
     */
    public function __construct(
        ResourceTag $resource,
        TagInterfaceFactory $tagFactory,
        TagCollectionFactory $tagCollectionFactory,
        TagSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        protected CmsPagesCollectionFactory $cmsPagesCollectionFactory,
        protected StoreManagerInterface $storeManager,
        protected Response $response,
        protected Logger $apiLogger,
        protected AuthorFactory $authorFactory
    ) {
        $this->resource = $resource;
        $this->tagFactory = $tagFactory;
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(TagInterface $tag)
    {
        try {
            $this->resource->save($tag);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the tag: %1',
                $exception->getMessage()
            ));
        }
        return $tag;
    }

    /**
     * @inheritDoc
     */
    public function get($tagId)
    {
        $tag = $this->tagFactory->create();
        $this->resource->load($tag, $tagId);
        if (!$tag->getId()) {
            throw new NoSuchEntityException(__('Tag with id "%1" does not exist.', $tagId));
        }
        return $tag;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->tagCollectionFactory->create();
        
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
    public function delete(TagInterface $tag)
    {
        try {
            $tagModel = $this->tagFactory->create();
            $this->resource->load($tagModel, $tag->getTagId());
            $this->resource->delete($tagModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Tag: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($tagId)
    {
        return $this->delete($this->get($tagId));
    }

    /**
     * @inheritDoc
     */
    public function getBlogTags(): array
    {
        $blogTags = [];
        $cmsTags = $this->cmsPagesCollectionFactory->create()
            ->addFieldToSelect('tag')
            ->addFieldToFilter('tag', ["neq" => null])
            ->addFieldToFilter('is_active', ["eq" => 1])
            ->distinct('tag')
            ->getColumnValues('tag');
        $tagIds = [];
        foreach ($cmsTags as $cmsTagIdsString) {
            $cmsTagIdsArray =  explode(',', $cmsTagIdsString);
            $tagIds = array_merge($tagIds, $cmsTagIdsArray);
            $tagIds = array_unique($tagIds);
        }

        $tagCollection = $this->tagCollectionFactory->create()
            ->addFieldToFilter('tag_id', ["in" => $tagIds]);

        foreach ($tagCollection as $tag) {
            $blogTags[] = $tag->getData();
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $blogTags
        );
    }

    /**
     * @inheritDoc
     */
    public function getBlogTagsByCategory(int $categoryId = 0): array
    {
        $blogTags = [];
        $cmsTags = $this->cmsPagesCollectionFactory->create()
            ->addFieldToSelect('tag')
            ->addFieldToSelect('category')
            ->addFieldToFilter('tag', ["neq" => null])
            ->addFieldToFilter('category', ["eq" => $categoryId])
            ->addFieldToFilter('is_active', ["eq" => 1])
            ->distinct('tag')
            ->getColumnValues('tag');
        $tagIds = [];
        foreach ($cmsTags as $cmsTagIdsString) {
            $cmsTagIdsArray =  explode(',', $cmsTagIdsString);
            $tagIds = array_merge($tagIds, $cmsTagIdsArray);
            $tagIds = array_unique($tagIds);
        }

        $tagCollection = $this->tagCollectionFactory->create()
            ->addFieldToFilter('tag_id', ["in" => $tagIds]);

        foreach ($tagCollection as $tag) {
            $blogTags[] = $tag->getData();
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $blogTags
        );
    }

    /**
     * @inheritDoc
     */
    public function getTaggedBlogs(string $tagUrlKey): array
    {
        $taggedBlogs = [];

        $tagCollection = $this->tagCollectionFactory->create()
            ->addFieldToFilter('url_key', ["eq" => $tagUrlKey]);
        if ($tagCollection->getSize()) {
            $tagId = $tagCollection->getFirstItem()->getId();

            $filteredBlogs = $this->cmsPagesCollectionFactory->create()
                ->addFieldToFilter('is_active', ["eq" => 1]);
            $filteredBlogs->setOrder('position', 'asc')->getItems();
            foreach ($filteredBlogs as $filteredBlog) {
                $tagIdsString = $filteredBlog->getTag() ?? '';
                $tagIdsArray =  explode(',', $tagIdsString);
                if (in_array($tagId, $tagIdsArray)) {
                    $taggedBlogs['tagged_blogs'][] = [
                        "identifier" => $filteredBlog->getIdentifier(),
                        "title" => $filteredBlog->getTitle(),
                        "content_heading" => $filteredBlog->getContentHeading(),
                        "thumbnail_image" => $this->getImageUrl($filteredBlog->getThumbnailImage()),
                        "short_description" => $filteredBlog->getShortDescription(),
                        "position" => $filteredBlog->getPosition(),
                        "author" => $filteredBlog->getAuthor() ? $this->getAuthorData($filteredBlog->getAuthor()) : [],
                        "is_top_blog" => $filteredBlog->getIsTopBlog(),
                        "is_new_blog" => $filteredBlog->getIsNewBlog(),
                        "view_count" => $filteredBlog->getViewCount() ?: 0
                    ];
                }
            }
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $taggedBlogs
        );
    }

    /**
     * @inheritDoc
     */
    public function getTaggedBlogsByCategory(string $tagUrlKey, int $categoryId = 0): array
    {
        $taggedBlogs = [];

        $tagCollection = $this->tagCollectionFactory->create()
            ->addFieldToFilter('url_key', ["eq" => $tagUrlKey]);
        if ($tagCollection->getSize()) {
            $tagId = $tagCollection->getFirstItem()->getId();

            $filteredBlogs = $this->cmsPagesCollectionFactory->create()
                ->addFieldToFilter('is_active', ["eq" => 1]);
            if ($categoryId) {
                $filteredBlogs->addFieldToFilter('category', ["eq" => $categoryId]);
            }
            $filteredBlogs->setOrder('position', 'asc')->getItems();
            foreach ($filteredBlogs as $filteredBlog) {
                $tagIdsString = $filteredBlog->getTag() ?? '';
                $tagIdsArray =  explode(',', $tagIdsString);
                if (in_array($tagId, $tagIdsArray)) {
                    $taggedBlogs['tagged_blogs'][] = [
                        "identifier" => $filteredBlog->getIdentifier(),
                        "title" => $filteredBlog->getTitle(),
                        "content_heading" => $filteredBlog->getContentHeading(),
                        "thumbnail_image" => $this->getImageUrl($filteredBlog->getThumbnailImage()),
                        "short_description" => $filteredBlog->getShortDescription(),
                        "position" => $filteredBlog->getPosition(),
                        "author" => $filteredBlog->getAuthor() ? $this->getAuthorData($filteredBlog->getAuthor()) : [],
                        "is_top_blog" => $filteredBlog->getIsTopBlog(),
                        "is_new_blog" => $filteredBlog->getIsNewBlog(),
                        "view_count" => $filteredBlog->getViewCount() ?: 0
                    ];
                }
            }
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $taggedBlogs
        );
    }

    /**
     * Get Image URL.
     *
     * @param  string|null $imageUrl
     * @return string|null
     */
    private function getImageUrl(?string $imageUrl): ?string
    {
        try {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            if ($imageUrl) {
                $imageUrl = str_replace('/', '', $imageUrl);
                return $mediaUrl . self::IMAGE_LOCATION . '/' . $imageUrl;
            }
        } catch (NoSuchEntityException $exception) {
            $this->apiLogger->critical($exception->getMessage() . __METHOD__);
        }
        return null;
    }

    /**
     * Get Author Data
     *
     * @param string $authorId
     * @return array
     */
    private function getAuthorData($authorId): array
    {
        $authorData = [];
        $author = $this->getAuthorByAuthorId($authorId);
        if ($author && $author->getData()) {
            $authorData = [
                'author_id' => $author->getAuthorId(),
                'author_name' => $author->getAuthorName()
            ];
        }

        return $authorData;
    }

    /**
     * Get Author By Author Id
     *
     * @param string $authorId
     * @return Author|null
     */
    private function getAuthorByAuthorId($authorId): Author
    {
        $author = $this->authorFactory->create()
            ->load($authorId);
        return $author;
    }
}
