<?php
/**
 * Pratech_SqsIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SqsIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SqsIntegration\Observer;

use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\SqsIntegration\Model\SqsEvent;
use Pratech\Base\Logger\Logger;
use Magento\Review\Model\Review;

class ReviewSaveAfter implements ObserverInterface
{
    /**
     * @param Logger                $logger
     * @param SqsEvent              $sqsEvent
     * @param CustomerRepository    $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository     $productRepository
     * @param CollectionFactory     $voteCollectionFactory
     */
    public function __construct(
        private Logger                $logger,
        private SqsEvent              $sqsEvent,
        private CustomerRepository    $customerRepository,
        private StoreManagerInterface $storeManager,
        private ProductRepository     $productRepository,
        private CollectionFactory     $voteCollectionFactory,
    ) {
    }

    public function execute(Observer $observer)
    {
        /** @var Review $review */
        $review = $observer->getEvent()->getData('object');

        try {

            $customerId = $review?->getCustomerId();
            if ((int)$review?->getStatusId() === Review::STATUS_APPROVED && $customerId) {
                $productId = $review->getEntityPkValue();

                $product = $this->productRepository->getById($productId);
                $mediaBaseUrl = $this->storeManager->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

                $customer = $this->customerRepository->getById($customerId);
                $email = $customer->getEmail();
                $phoneNumber = $customer->getCustomAttribute('mobile_number')?->getValue();


                $voteCollection = $this->voteCollectionFactory->create()
                    ->setReviewFilter($review->getId())
                    ->setStoreFilter($review->getStoreId())
                    ->addRatingInfo()
                    ->load();

                $ratings = [];
                foreach ($voteCollection as $vote) {
                    $ratings[] = [
                        'rating_code' => $vote->getRatingCode(),
                        'percent' => $vote->getPercent()
                    ];
                }

                $data = [
                    'type' => 'email',
                    'event_name' => 'REVIEW_APPROVED',
                    'title' => $review->getTitle(),
                    'detail' => $review->getDetail(),
                    'nickname' => $review->getNickname(),
                    'customer_id' => $customerId,
                    'email' => $email,
                    'phonenumber' => $phoneNumber,
                    'ratings' => $ratings,
                    'items' => [
                        'image' => $mediaBaseUrl . 'catalog/product' . $product->getImage(),
                        'name' => $product->getName(),
                        'price' => $product->getPrice() ? number_format($product->getPrice(), 2) : 0,
                        'sku' => $product->getSku()
                    ]
                ];

                $this->sqsEvent->sentEmailEventToSqs($data);
            }
        } catch (Exception $exception) {
            $this->logger->info($exception->getMessage() . __METHOD__);
        }
    }
}
