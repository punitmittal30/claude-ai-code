<?php

namespace Pratech\ReviewRatings\Helper;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Store\Model\StoreManager;
use Pratech\Catalog\Helper\Eav;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory as StatusCollectionFactory;
use Pratech\ReviewRatings\Api\Data\MediaDataInterface;
use Pratech\ReviewRatings\Model\Config\Source\MediaStatus;
use Pratech\ReviewRatings\Model\MediaFactory as ReviewMediaFactory;
use Pratech\ReviewRatings\Model\ResourceModel\Keywords\CollectionFactory as KeywordsCollectionFactory;
use Pratech\ReviewRatings\Model\ResourceModel\Media\CollectionFactory as ReviewMediaCollectionFactory;
use Pratech\StoreCredit\Helper\Config as StoreCreditConfig;

/**
 * Review Ratings Helper Class
 */
class Data
{
    /**
     * Review Media Image Path
     */
    public const IMAGE_PATH = 'review/images';

    /**
     * @var $reviewsCollection
     */
    protected $reviewsCollection;

    /**
     * Review Helper Constructor
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param StoreManager $storeManager
     * @param Product $product
     * @param ProductRepositoryInterface $productRepository
     * @param ReviewFactory $reviewFactory
     * @param RatingFactory $ratingFactory
     * @param Option $ratingOptions
     * @param SearchCriteriaInterface $searchCriteria
     * @param FilterBuilder $filterBuilder
     * @param FilterGroup $filterGroup
     * @param ManagerInterface $eventManager
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param Eav $eavHelper
     * @param ReviewMediaFactory $reviewMediaFactory
     * @param ReviewMediaCollectionFactory $reviewMediaCollectionFactory
     * @param KeywordsCollectionFactory $keywordsCollectionFactory
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param StoreCreditConfig $storeCreditConfig
     */
    public function __construct(
        private CustomerRepositoryInterface  $customerRepository,
        private ReviewCollectionFactory      $reviewCollectionFactory,
        private StoreManager                 $storeManager,
        private Product                      $product,
        private ProductRepositoryInterface   $productRepository,
        private ReviewFactory                $reviewFactory,
        private RatingFactory                $ratingFactory,
        private Option                       $ratingOptions,
        private SearchCriteriaInterface      $searchCriteria,
        private FilterBuilder                $filterBuilder,
        private FilterGroup                  $filterGroup,
        private ManagerInterface             $eventManager,
        private ShipmentCollectionFactory    $shipmentCollectionFactory,
        private Eav                          $eavHelper,
        private ReviewMediaFactory           $reviewMediaFactory,
        private ReviewMediaCollectionFactory $reviewMediaCollectionFactory,
        private KeywordsCollectionFactory    $keywordsCollectionFactory,
        private StatusCollectionFactory      $statusCollectionFactory,
        private StoreCreditConfig            $storeCreditConfig,
    ) {
    }

    /**
     * Get Product Review Form Data
     *
     * @param string $productSlug
     * @param int $customerId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductReviewFormData(string $productSlug, int $customerId): array
    {
        $result = [];
        $isCustomerAllowed = false;
        $hasCustomerReviewed = false;

        $productId = $this->getProductIdByUrl($productSlug);
        $statusCollection = $this->statusCollectionFactory->create()
            ->addFieldToFilter('status_code', 'delivered');
        if ($statusCollection->getSize() > 0) {
            $deliveredStatusId = $statusCollection->getFirstItem()->getStatusId();
            $deliveredShipmentCollection = $this->shipmentCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('shipment_status', $deliveredStatusId)
                ->addAttributeToSort('created_at', 'desc');
            foreach ($deliveredShipmentCollection as $shipment) {
                $shipmentItems = $shipment->getItems();
                foreach ($shipmentItems as $item) {
                    // Retrieve product information for each ordered item
                    $deliveredProductId = $item->getProductId();
                    if ($deliveredProductId == $productId) {
                        $isCustomerAllowed = true;
                        break 2;
                    }
                }
            }
        }
        $result["is_customer_allowed"] = $isCustomerAllowed;

        $reviewsCollection = $this->reviewCollectionFactory->create()
            ->addEntityFilter('product', $productId)
            ->addFieldToFilter('customer_id', $customerId);
        if ($reviewsCollection->getSize() > 0) {
            $hasCustomerReviewed = true;
        }
        $result["has_customer_reviewed"] = $hasCustomerReviewed;

        if ($isCustomerAllowed && !$hasCustomerReviewed) {
            $product = $this->productRepository->getById($productId);
            $productData = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'status' => $product->getStatus(),
                'sku' => $product->getSku(),
                'type' => $product->getTypeId(),
                'slug' => $product->getCustomAttribute('url_key')->getValue(),
                'image' => $product->getImage(),
                'item_weight' => $product->getCustomAttribute('item_weight')
                    ? $this->eavHelper->getOptionLabel(
                        'item_weight',
                        $product->getCustomAttribute('item_weight')->getValue()
                    ) : "",
                'flavour' => $product->getCustomAttribute('flavour')
                    ? $this->eavHelper->getOptionLabel(
                        'flavour',
                        $product->getCustomAttribute('flavour')->getValue()
                    ) : "",
                'number_of_servings' => $product->getCustomAttribute('number_of_servings') ?
                    $product->getCustomAttribute('number_of_servings')->getValue() : ""
            ];

            $categorization = "";
            if ($product->getCustomAttribute('categorization')) {
                $categorization = $this->eavHelper->getOptionLabel(
                    'categorization',
                    $product->getCustomAttribute('categorization')->getValue()
                );
            }
            $productReviewKeywords = $this->getReviewKeywordsForProduct($categorization);
            $productData["review_keywords"] = !empty($productReviewKeywords) ? $productReviewKeywords : null;

            $result["product"] = $productData;
        }

        return $result;
    }

    /**
     * Get Product ID By Product Slug
     *
     * @param string $url
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getProductIdByUrl(string $url): ?int
    {
        $this->filterGroup->setFilters([
            $this->filterBuilder->setField('url_key')->setConditionType('eq')
                ->setValue($url)->create()]);
        $this->searchCriteria->setFilterGroups([$this->filterGroup]);
        $products = $this->productRepository->getList($this->searchCriteria);
        if (count($products->getItems()) == 0) {
            throw new NoSuchEntityException(
                __("The product that was requested doesn't exist. Verify the product and try again.")
            );
        }
        $items = $products->getItems();
        foreach ($items as $item) {
            return $item->getId();
        }
        return null;
    }

    /**
     * Get review keywords for Product
     *
     * @param string $mappingValue
     * @return array
     */
    public function getReviewKeywordsForProduct($mappingValue): array
    {
        if (empty($mappingValue)) {
            $mappingValue = "Default";
        }

        $keywordsCollection = $this->keywordsCollectionFactory->create();
        $keywordsCollection->addFieldToFilter(
            'mapping_value',
            ['eq' => $mappingValue]
        );
        foreach ($keywordsCollection as $keywords) {
            return $keywords->getData();
        }

        return [];
    }

    /**
     * Get Ratings
     *
     * @param int|null $storeId
     * @return array
     */
    public function getRatings(?int $storeId = null): array
    {
        $ratingCollection = $this->ratingFactory->create()->getCollection();
        $ratingCollection->addFieldToFilter(
            'store_id',
            ['eq' => $storeId]
        );
        $ratingCollection->join(
            ['rating_store' => 'rating_store'],
            'main_table.rating_id = rating_store.rating_id'
        );

        return $ratingCollection->getData();
    }

    /**
     * Write Product Reviews
     *
     * @param int $productId
     * @param string $nickname
     * @param string $title
     * @param string $detail
     * @param array $ratingData
     * @param int|null $customerId
     * @param string $keywords
     * @param MediaDataInterface[] $mediaData
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function writeReviews(
        int    $productId,
        string $nickname,
        string $title,
        string $detail,
        array  $ratingData,
        ?int   $customerId = null,
        string $keywords = '',
        array  $mediaData = []
    ): array {
        $status = $message = "";
        $storeId = $this->storeManager->getStore()->getId();
        if ($customerId) {
            $customerData = $this->customerRepository->getById($customerId);
            $isReviewed = $customerData->getCustomAttribute('is_reviewed')
                ? $customerData->getCustomAttribute('is_reviewed')->getValue()
                : 0;
            $isReviewCashback = $this->storeCreditConfig->isFirstReviewCashbackEnabled();
            $fixedAmount = $this->storeCreditConfig->getFirstReviewCashbackAmount();
        }

        $data = [
            "nickname" => $nickname,
            "title" => $title,
            "keywords" => $keywords,
            "detail" => $detail
        ];
//        if (empty($title)) {
//            throw new InputException(__('Not a valid Title'));
//        }

        $ratings = [];
        if (empty($ratingData)) {
            throw new InputException(__('Not a valid rating data'));
        }

        //map vote option id with the star value
        foreach ($ratingData as $rating) {
            $ratings[$rating->getRatingId()] = $this->getVoteOption($rating->getRatingId(), $rating->getRatingValue());
        }

        $product = $this->product->load($productId);
        if (!$product->getId()) {
            throw new NoSuchEntityException(__('Product doesn\'t exist'));
        }
        if (!empty($data)) {
            $review = $this->reviewFactory->create()->setData($data);
            $review->unsetData('review_id');
//           $validate = $review->validate();
            try {
                $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                    ->setEntityPkValue($product->getId())
                    ->setStatusId(Review::STATUS_PENDING)
                    ->setCustomerId($customerId)
                    ->setStoreId($storeId)
                    ->setStores([$storeId])
                    ->save();
                if (count($ratings)) {
                    foreach ($ratings as $ratingId => $optionId) {
                        $this->ratingFactory->create()
                            ->setRatingId($ratingId)
                            ->setReviewId($review->getId())
                            ->setCustomerId($customerId)
                            ->addOptionVote($optionId, $product->getId());
                    }
                }
                if (count($mediaData)) {
                    $mediaCount = 0;
                    foreach ($mediaData as $file) {
                        if ($mediaCount == 5) {
                            break;
                        }
                        $this->reviewMediaFactory->create()
                            ->setReviewId($review->getId())
                            ->setStatus(MediaStatus::PENDING)
                            ->setType($file->getMediaType())
                            ->setUrl($file->getUrl())
                            ->save();

                        $mediaCount++;
                    }
                }
                $review->aggregate();
                $status = true;
                if ($customerId && $isReviewCashback && $fixedAmount > 0 && $isReviewed == 0) {
                    $message = 'Thank you for submitting the review. Your review is awaiting approval!' . " "
                        . 'When your review is approved than you get ' . $fixedAmount . " " . 'cashback';
                } else {
                    $message = 'Thank you for submitting the review. Your review is awaiting approval!';
                }
                // Dispatch the review_save_after event
                $this->eventManager->dispatch('review_save_after', ['review' => $review]);
            } catch (Exception $e) {
                $message = 'We can\'t post your review right now. ' . $e->getMessage();
                $status = false;
            }
        }

        return [
            "status" => $status,
            "message" => $message
        ];
    }

    /**
     * Get Vote Option
     *
     * @param int $ratingId
     * @param int $value
     * @return int
     */
    public function getVoteOption(int $ratingId, int $value): int
    {
        $optionId = 0;
        $ratingOptionCollection = $this->ratingOptions->getCollection()
            ->addFieldToFilter('rating_id', $ratingId)
            ->addFieldToFilter('value', $value);
        if (count($ratingOptionCollection)) {
            foreach ($ratingOptionCollection as $row) {
                $optionId = $row->getOptionId();
            }
        }
        return $optionId;
    }

    /**
     * Get Reviews By Product Slug
     *
     * @param string $productSlug
     * @return array
     * @throws NoSuchEntityException
     */
    public function getReviewsByProductSlug(string $productSlug): array
    {
        $productId = $this->getProductIdByUrl($productSlug);
        return $this->getReviewsByProductId($productId);
    }

    /**
     * Get Reviews By Product ID
     *
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getReviewsByProductId(int $productId): array
    {
        $starRating = 0;

        $ratingsDistribution = [
            "1" => 0,
            "2" => 0,
            "3" => 0,
            "4" => 0,
            "5" => 0
        ];
        if (null === $this->reviewsCollection) {
            $this->reviewsCollection = $this->reviewCollectionFactory->create()
                ->addStatusFilter(
                    Review::STATUS_APPROVED
                )->addEntityFilter(
                    'product',
                    $productId
                )->setDateOrder();
        }
        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->product->load($productId);

        $this->reviewFactory->create()->getEntitySummary(
            $product,
            $storeId
        );
        $ratingSummary = $product->getRatingSummary()->getRatingSummary();
        $reviewArray = [];
        $reviewCollection = $this->reviewsCollection;
        $collection = $reviewCollection->load()->addRateVotes();
        $count = count($collection);
        foreach ($collection as $reviewCollection) {
            $rating = $reviewCollection->getRatingVotes()->getData();
            $votes = [];
            foreach ($rating as $rate) {
                $vote['ratingPercent'] = $rate['percent'];
                $vote['ratingValue'] = $rate['value'];
                $vote['ratingCode'] = $rate['rating_code'];
                $ratingsDistribution[$rate['value']]++;
                $votes[] = $vote;
            }
            $reviewMedia = $this->getReviewMedia([$reviewCollection->getReviewId()], MediaStatus::APPROVED);
            $data = [
                "review_id" => $reviewCollection->getReviewId(),
                "created_at" => $reviewCollection->getCreatedAt(),
                "title" => $reviewCollection->getTitle(),
                "detail" => $reviewCollection->getDetail(),
                "nickname" => $reviewCollection->getNickname(),
                "customer_id" => $reviewCollection->getCustomerId(),
                "power_review" => $reviewCollection->getPowerReview() ? true : false,
                "position" => $reviewCollection->getPosition(),
                "rating_votes" => $votes,
                "keywords" => $reviewCollection->getKeywords() ?: '',
                "media" => $reviewMedia
            ];
            $reviewArray[] = $data;
        }

        if ($count > 0) {
            foreach ($ratingsDistribution as &$rating) {
                $rating = round($rating / $count * 100, 2);
            }
            $starRating = round($ratingSummary / 20, 2);
        }

        return [
            "avg_rating_percent" => $ratingSummary,
            "star_rating" => $starRating,
            "count" => $count,
            "ratings_distribution" => $ratingsDistribution,
            "reviews" => $reviewArray
        ];
    }

    /**
     * Get review media
     *
     * @param array $reviewIds
     * @param int|null $status
     * @return array
     */
    public function getReviewMedia(array $reviewIds = [], int $status = null): array
    {
        $mediaCollection = $this->reviewMediaCollectionFactory->create();
        if (!empty($reviewIds)) {
            $mediaCollection->addFieldToFilter(
                'review_id',
                ['in' => $reviewIds]
            );
        }
        if ($status) {
            $mediaCollection->addFieldToFilter(
                'status',
                ['eq' => $status]
            );
        }
        if ($mediaCollection->getSize()) {
            return $mediaCollection->getData();
        }
        return [];
    }

    /**
     * Set Product Reviews from order shipments
     *
     * @param string $nickname
     * @param int $customerId
     * @param array $productReviewData
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function setProductReviews(
        string $nickname,
        int    $customerId,
        array  $productReviewData
    ): array {
        $status = $message = "";
        $storeId = $this->storeManager->getStore()->getId();
        if ($customerId) {
            $customerData = $this->customerRepository->getById($customerId);
            $isReviewed = $customerData->getCustomAttribute('is_reviewed')
                ? $customerData->getCustomAttribute('is_reviewed')->getValue()
                : 0;
            $isReviewCashback = $this->storeCreditConfig->isFirstReviewCashbackEnabled();
            $fixedAmount = $this->storeCreditConfig->getFirstReviewCashbackAmount();
        }

        if (empty($productReviewData)) {
            throw new InputException(__('Not a valid rating data'));
        }
        foreach ($productReviewData as $reviewData) {
            $data = [
                "nickname" => $nickname,
                "title" => $reviewData->getTitle() ?: '',
                "keywords" => $reviewData->getKeywords() ?: '',
                "detail" => $reviewData->getDetail() ?: ''
            ];

            $ratings = [];
            $ratingData = $reviewData->getRatingData();
            if (empty($ratingData)) {
                throw new InputException(__('Not a valid rating data'));
            }

            //map vote option id with the star value
            foreach ($ratingData as $rating) {
                $ratings[$rating->getRatingId()] = $this->getVoteOption(
                    $rating->getRatingId(),
                    $rating->getRatingValue()
                );
            }

            $product = $this->productRepository->get($reviewData->getSku());
            if (!empty($data)) {
                $review = $this->reviewFactory->create()->setData($data);
                $review->unsetData('review_id');
                //   $validate = $review->validate();
                try {
                    $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                        ->setEntityPkValue($product->getId())
                        ->setStatusId(Review::STATUS_PENDING)
                        ->setCustomerId($customerId)
                        ->setStoreId($storeId)
                        ->setStores([$storeId])
                        ->save();
                    if (count($ratings)) {
                        foreach ($ratings as $ratingId => $optionId) {
                            $this->ratingFactory->create()
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->setCustomerId($customerId)
                                ->addOptionVote($optionId, $product->getId());
                        }
                    }
                    $mediaData = $reviewData->getMediaData();
                    if (count($mediaData)) {
                        $mediaCount = 0;
                        foreach ($mediaData as $file) {
                            if ($mediaCount == 5) {
                                break;
                            }
                            $this->reviewMediaFactory->create()
                                ->setReviewId($review->getId())
                                ->setStatus(MediaStatus::PENDING)
                                ->setType($file->getMediaType())
                                ->setUrl($file->getUrl())
                                ->save();

                            $mediaCount++;
                        }
                    }
                    $review->aggregate();
                    $status = true;

                    // Dispatch the review_save_after event
                    $this->eventManager->dispatch('review_save_after', ['review' => $review]);
                } catch (Exception $e) {
                    $message = 'We can\'t post your review right now. ' . $e->getMessage();
                    $status = false;
                }
            }
        }

        if ($status) {
            if ($customerId && $isReviewCashback && $fixedAmount > 0 && $isReviewed == 0) {
                $message = 'Thank you for submitting the review. Your review is awaiting approval!' . " "
                    . 'When your review is approved than you get ' . $fixedAmount . " " . 'cashback';
            } else {
                $message = 'Thank you for submitting the review. Your review is awaiting approval!';
            }
        }

        return [
            "status" => $status,
            "message" => $message,
        ];
    }

    /**
     * Get review keywords By entity type
     *
     * @return array
     */
    public function getReviewKeywordsForShipment(): array
    {
        $entityType = "order";

        $keywordsCollection = $this->keywordsCollectionFactory->create();
        $keywordsCollection->addFieldToFilter(
            'entity_type',
            ['eq' => $entityType]
        );
        foreach ($keywordsCollection as $keywords) {
            return $keywords->getData();
        }
        return [];
    }

    /**
     * Get Has Customer Reviewed
     *
     * @param string $productSlug
     * @param int $customerId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getHasCustomerReviewed(string $productSlug, int $customerId): bool
    {
        $hasCustomerReviewed = false;
        $productId = $this->getProductIdByUrl($productSlug);
        $reviewsCollection = $this->reviewCollectionFactory->create()
            ->addEntityFilter('product', $productId)
            ->addFieldToFilter('customer_id', $customerId);
        if ($reviewsCollection->getSize() > 0) {
            $hasCustomerReviewed = true;
        }
        return $hasCustomerReviewed;
    }
}
