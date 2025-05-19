<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as CmsPagesCollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Model\Data\Response;
use Pratech\Blog\Api\CategoryRepositoryInterface;
use Pratech\CmsBlock\Api\CmsPageInterface;
use Pratech\CmsBlock\Model\Author;
use Pratech\CmsBlock\Model\AuthorFactory;

/**
 * Cms Page class to expose apis related to blogs.
 */
class CmsPage implements CmsPageInterface
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
     * TOP BLOGS WIDGET NAME CONFIG CONSTANT
     */
    public const TOP_BLOGS_WIDGET_NAME = 'blogs/general/widget_name';

    /**
     * TOP BLOGS COUNT CONFIG CONSTANT
     */
    public const TOP_BLOGS_COUNT = 'blogs/general/no_of_blog_cards';

    /**
     * NEW BLOGS WIDGET NAME CONFIG CONSTANT
     */
    public const NEW_BLOGS_WIDGET_NAME = 'blogs/general/widget_name';

    /**
     * NEW BLOGS COUNT CONFIG CONSTANT
     */
    public const NEW_BLOGS_COUNT = 'blogs/general/no_of_blog_cards';

    /**
     * Cms Page Constructor
     *
     * @param StoreManagerInterface        $storeManager
     * @param GetPageByIdentifierInterface $getPageByIdentifier
     * @param FilterProvider               $filter
     * @param CategoryRepositoryInterface  $categoryRepository
     * @param ProductRepositoryInterface   $productRepository
     * @param CmsPagesCollectionFactory    $cmsPagesCollectionFactory
     * @param PageRepositoryInterface      $pageRepository
     * @param Response                     $response
     * @param Logger                       $apiLogger
     * @param ScopeConfigInterface         $scopeConfig
     * @param AuthorFactory                $authorFactory
     */
    public function __construct(
        private StoreManagerInterface        $storeManager,
        private GetPageByIdentifierInterface $getPageByIdentifier,
        private FilterProvider               $filter,
        private CategoryRepositoryInterface  $categoryRepository,
        private ProductRepositoryInterface   $productRepository,
        private CmsPagesCollectionFactory    $cmsPagesCollectionFactory,
        private PageRepositoryInterface      $pageRepository,
        private Response                     $response,
        private Logger                       $apiLogger,
        private ScopeConfigInterface         $scopeConfig,
        private AuthorFactory                $authorFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getBlogByIdentifier(string $identifier): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $this->getFormattedBlogDataByIdentifier($identifier)
        );
    }

    /**
     * Get Formatted Blog Data
     *
     * @param  string $identifier
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    private function getFormattedBlogDataByIdentifier(string $identifier): array
    {
        $storeId = $this->storeManager->getWebsite(0)->getDefaultStore()->getId();
        $blog = $this->getPageByIdentifier->execute($identifier, $storeId);
        if (!$blog->getId() || !$blog->isActive()) {
            throw new NoSuchEntityException(__('CMS Page with identifier "%1" does not exist.', $identifier));
        }

        return [
            "id" => $blog->getId(),
            "identifier" => $blog->getIdentifier(),
            "title" => $blog->getTitle(),
            "meta_title" => $blog->getMetaTitle(),
            "meta_keywords" => $blog->getMetaKeywords(),
            "meta_description" => $blog->getMetaDescription(),
            "content_heading" => $blog->getContentHeading(),
            "thumbnail_image" => $this->getImageUrl($blog->getThumbnailImage()),
            "featured_image" => $this->getImageUrl($blog->getFeaturedImage()),
            "short_description" => $blog->getShortDescription(),
            "content" => $this->filterStaticPageContent($blog->getContent()),
            "creation_time" => $blog->getCreationTime(),
            "update_time" => $blog->getUpdateTime(),
            "sort_order" => $blog->getSortOrder(),
            "category" => $blog->getCategory(),
            "author" => $blog->getAuthor() ? $this->getAuthorData($blog->getAuthor()) : [],
            "is_top_blog" => $blog->getIsTopBlog(),
            "is_new_blog" => $blog->getIsNewBlog(),
            "view_count" => $blog->getViewCount() ?: 0,
            "recommended_products" => $this->getRecommendedProducts($blog),
            "health_tip" => $blog->getHealthTip()
        ];
    }

    /**
     * Get Recommended Products
     *
     * @param CmsPage $blog
     * @return array
     */
    private function getRecommendedProducts($blog): array
    {
        $recommendedProducts = [];
        $recommendedProductSkuString = $blog->getRecommendedProducts() ?? '';
        $recommendedProductSkuArray = array_unique(explode(',', $recommendedProductSkuString));
        foreach ($recommendedProductSkuArray as $productSku) {
            $productSku = trim($productSku);
            if ($productSku) {
                try {
                    $product = $this->productRepository->get($productSku);
                    $productData = [
                        'name' => $product->getName(),
                        'sku' => $product->getSku(),
                        'status' => $product->getStatus(),
                        'type' => $product->getTypeId(),
                        'slug' => $product->getCustomAttribute('url_key')->getValue(),
                        'image' => $product->getImage(),
                    ];
                    $recommendedProducts[] = $productData;
                } catch (Exception $exception) {
                    continue;
                }
            }
        }
        return $recommendedProducts;
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
     * Get Image URL.
     *
     * @param  string|null $imageUrl
     * @return string|null
     */
    private function getCategoryThumbnailImageUrl(?string $imageUrl): ?string
    {
        if ($imageUrl) {
            $imageUrl = str_replace('/', '', $imageUrl);
            return '/' . self::IMAGE_LOCATION . '/' . $imageUrl;
        }
        return null;
    }

    /**
     * Filter Static Block Content
     *
     * @param  string|null $blogContent
     * @return string
     * @throws Exception
     */
    public function filterStaticPageContent(?string $blogContent): string
    {
        return $this->filter->getPageFilter()->filter($blogContent);
    }

    /**
     * @inheritDoc
     */
    public function getBlogCategories(): array
    {
        $blogCategories = [];

        $cmsCategoryIds = $this->cmsPagesCollectionFactory->create()
            ->addFieldToSelect('category')
            ->addFieldToFilter('category', ["neq" => null])
            ->addFieldToFilter('is_active', ["eq" => 1])
            ->distinct('category')
            ->setOrder('category', 'asc')
            ->getColumnValues('category');

        $cmsCategories = $this->categoryRepository->getCategories($cmsCategoryIds);
        $cmsCategories->addFieldToFilter('category_id', ['in' => $cmsCategoryIds]);
        foreach ($cmsCategories as $categoryData) {
            $blogCategories[] = [
                "id" => $categoryData->getId(),
                "name" => $categoryData->getName(),
                "url_key" => $categoryData->getUrlKey(),
                "image" => $this->getCategoryThumbnailImageUrl($categoryData->getThumbnailImage()),
                "image_mobile" => $this->getCategoryThumbnailImageUrl($categoryData->getThumbnailImageMobile()),
                "image_app" => $this->getCategoryThumbnailImageUrl($categoryData->getThumbnailImageApp())
            ];
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $blogCategories
        );
    }

    /**
     * Remove Media From Url
     *
     * @param  string $url
     * @return string
     */
    public function removeMediaFromUrl(string $url): string
    {
        if (str_contains($url, '/media')) {
            $url = explode('/media', $url, 2)[1];
        }
        return $url;
    }

    /**
     * @inheritDoc
     */
    public function getBlogs(SearchCriteriaInterface $searchCriteria): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $this->getBlogsBySearchCriteria($searchCriteria)
        );
    }

    /**
     * Get Blogs Details By Search Criteria.
     *
     * @param  SearchCriteriaInterface $searchCriteria
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    private function getBlogsBySearchCriteria(SearchCriteriaInterface $searchCriteria): array
    {
        $topContent = null;
        $cmsCategoryId = null;
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                if ($condition == 'eq' && $filter->getField() == 'category') {
                    $cmsCategoryId = $filter->getValue();
                }
            }
        }
        $categoryData = null;
        if ($cmsCategoryId) {
            try {
                $categoryData = $this->categoryRepository->get($cmsCategoryId);
            } catch (Exception $exception) {
                $this->apiLogger->error($exception->getMessage() . __METHOD__);
            }
        } else {
            try {
                $categoryData = $this->categoryRepository->getByUrlKey('home');
            } catch (Exception $exception) {
                $this->apiLogger->error($exception->getMessage() . __METHOD__);
            }
        }
        if ($categoryData) {
            $topContent = [
                "title" => $categoryData->getTitle(),
                "description" => $categoryData->getDescription(),
                "banner_image" => $categoryData->getBannerImage()
                    ? $this->getImageUrl($categoryData->getBannerImage())
                    : null,
                "banner_image_mobile" => $categoryData->getBannerImageMobile()
                    ? $this->getImageUrl($categoryData->getBannerImageMobile())
                    : null,
                "banner_image_app" => $categoryData->getBannerImageApp()
                    ? $this->getImageUrl($categoryData->getBannerImageApp())
                    : null,
            ];
            if ($cmsCategoryId) {
                $topContent = array_merge(
                    [
                        "category_id" => $cmsCategoryId,
                        "name" => $categoryData->getName(),
                        "url_key" => $categoryData->getUrlKey()
                    ],
                    $topContent
                );
            }
        }

        $items = [];
        $blogs = $this->pageRepository->getList($searchCriteria);
        foreach ($blogs->getItems() as $blog) {
            $items[] = [
                "id" => $blog->getId(),
                "identifier" => $blog->getIdentifier(),
                "title" => $blog->getTitle(),
                "meta_title" => $blog->getMetaTitle(),
                "meta_keywords" => $blog->getMetaKeywords(),
                "meta_description" => $blog->getMetaDescription(),
                "content_heading" => $blog->getContentHeading(),
                "thumbnail_image" => $this->getImageUrl($blog->getThumbnailImage()),
                "featured_image" => $this->getImageUrl($blog->getFeaturedImage()),
                "short_description" => $blog->getShortDescription(),
                "content" => $this->filterStaticPageContent($blog->getContent()),
                "creation_time" => $blog->getCreationTime(),
                "update_time" => $blog->getUpdateTime(),
                "sort_order" => $blog->getSortOrder(),
                "category" => $blog->getCategory(),
                "author" => $blog->getAuthor() ? $this->getAuthorData($blog->getAuthor()) : [],
                "is_top_blog" => $blog->getIsTopBlog(),
                "is_new_blog" => $blog->getIsNewBlog(),
                "view_count" => $blog->getViewCount() ?: 0
            ];
        }
        return [
            "top_content" => $topContent,
            "items" => $items,
            "total_count" => $blogs->getTotalCount(),
            "page_size" => $blogs->getSearchCriteria()->getPageSize(),
            "current_page" => $blogs->getSearchCriteria()->getCurrentPage()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRelatedArticles(string $identifier): array
    {
        $relatedBlogs = [];

        $storeId = $this->storeManager->getWebsite(0)->getDefaultStore()->getId();
        $blog = $this->getPageByIdentifier->execute($identifier, $storeId);
        $relatedBlogIdsString = $blog->getRelatedBlogs() ?? '';
        $relatedBlogIdsArray = explode(',', $relatedBlogIdsString);
        foreach ($relatedBlogIdsArray as $key => $relatedBlogId) {
            $relatedBlogId = trim($relatedBlogId);
            if (!$relatedBlogId) {
                unset($relatedBlogIdsArray[$key]);
            }
        }

        if (count($relatedBlogIdsArray)) {
            $filteredBlogs = $this->cmsPagesCollectionFactory->create()
                ->addFieldToFilter('is_active', ["eq" => 1])
                ->addFieldToFilter('page_id', ["in" => $relatedBlogIdsArray])
                ->setOrder('position', 'asc')->getItems();

            foreach ($filteredBlogs as $filteredBlog) {
                $relatedBlogs['related_blogs'][] = [
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

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $relatedBlogs
        );
    }

    /**
     * @inheritDoc
     */
    public function getTopBlogs(): array
    {
        $topBlogs = [];

        $topBlogs['widget_name'] = $this->getConfigValue(self::TOP_BLOGS_WIDGET_NAME);
        $noOfTopBlogsToDisplay = $this->getConfigValue(self::TOP_BLOGS_COUNT);
        $filteredBlogs = $this->cmsPagesCollectionFactory->create()
            ->addFieldToFilter('is_active', ["eq" => 1])
            ->addFieldToFilter('is_top_blog', ["eq" => 1]);
        $filteredBlogs->setPageSize($noOfTopBlogsToDisplay)
            ->setOrder('position', 'asc')->getItems();

        foreach ($filteredBlogs as $filteredBlog) {
            $topBlogs['top_blogs'][] = [
                "identifier" => $filteredBlog->getIdentifier(),
                "title" => $filteredBlog->getTitle(),
                "content_heading" => $filteredBlog->getContentHeading(),
                "thumbnail_image" => $this->getImageUrl($filteredBlog->getThumbnailImage()),
                "short_description" => $filteredBlog->getShortDescription(),
                "position" => $filteredBlog->getPosition(),
                "author" => $filteredBlog->getAuthor() ? $this->getAuthorData($filteredBlog->getAuthor()) : [],
                "is_new_blog" => $filteredBlog->getIsNewBlog(),
                "view_count" => $filteredBlog->getViewCount() ?: 0
            ];
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $topBlogs
        );
    }

    /**
     * @inheritDoc
     */
    public function getTopBlogsByCategory(int $categoryId = 0): array
    {
        $topBlogs = [];

        $topBlogs['widget_name'] = $this->getConfigValue(self::TOP_BLOGS_WIDGET_NAME);
        $noOfTopBlogsToDisplay = $this->getConfigValue(self::TOP_BLOGS_COUNT);
        $filteredBlogs = $this->cmsPagesCollectionFactory->create()
            ->addFieldToFilter('is_active', ["eq" => 1])
            ->addFieldToFilter('is_top_blog', ["eq" => 1]);
        if ($categoryId) {
            $filteredBlogs->addFieldToFilter('category', ["eq" => $categoryId]);
        }
        $filteredBlogs->setPageSize($noOfTopBlogsToDisplay)
            ->setOrder('position', 'asc')->getItems();

        foreach ($filteredBlogs as $filteredBlog) {
            $topBlogs['top_blogs'][] = [
                "identifier" => $filteredBlog->getIdentifier(),
                "title" => $filteredBlog->getTitle(),
                "content_heading" => $filteredBlog->getContentHeading(),
                "thumbnail_image" => $this->getImageUrl($filteredBlog->getThumbnailImage()),
                "short_description" => $filteredBlog->getShortDescription(),
                "position" => $filteredBlog->getPosition(),
                "author" => $filteredBlog->getAuthor() ? $this->getAuthorData($filteredBlog->getAuthor()) : [],
                "is_new_blog" => $filteredBlog->getIsNewBlog(),
                "view_count" => $filteredBlog->getViewCount() ?: 0
            ];
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $topBlogs
        );
    }

    /**
     * @inheritDoc
     */
    public function getNewBlogs(): array
    {
        $newBlogs = [];

        $newBlogs['widget_name'] = $this->getConfigValue(self::NEW_BLOGS_WIDGET_NAME);
        $noOfNewBlogsToDisplay = $this->getConfigValue(self::NEW_BLOGS_COUNT);
        $filteredBlogs = $this->cmsPagesCollectionFactory->create()
            ->addFieldToFilter('is_active', ["eq" => 1])
            ->addFieldToFilter('is_new_blog', ["eq" => 1])
            ->setPageSize($noOfNewBlogsToDisplay)
            ->setOrder('position', 'asc')->getItems();

        foreach ($filteredBlogs as $filteredBlog) {
            $newBlogs['new_blogs'][] = [
                "identifier" => $filteredBlog->getIdentifier(),
                "title" => $filteredBlog->getTitle(),
                "content_heading" => $filteredBlog->getContentHeading(),
                "thumbnail_image" => $this->getImageUrl($filteredBlog->getThumbnailImage()),
                "short_description" => $filteredBlog->getShortDescription(),
                "position" => $filteredBlog->getPosition(),
                "author" => $filteredBlog->getAuthor() ? $this->getAuthorData($filteredBlog->getAuthor()) : [],
                "is_top_blog" => $filteredBlog->getIsTopBlog(),
                "view_count" => $filteredBlog->getViewCount() ?: 0
            ];
        }

        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::BLOGS_API_RESOURCE,
            $newBlogs
        );
    }

    /**
     * Get Scope Config Value
     *
     * @param  string $config
     * @return mixed
     */
    public function getConfigValue(string $config): mixed
    {
        return $this->scopeConfig->getValue(
            $config,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Author Data
     *
     * @param  int $authorId
     * @return array
     */
    public function getAuthorData(int $authorId): array
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
     * @param  int $authorId
     * @return Author|null
     */
    public function getAuthorByAuthorId(int $authorId): Author
    {
        $author = $this->authorFactory->create()
            ->load($authorId);
        return $author;
    }
}
