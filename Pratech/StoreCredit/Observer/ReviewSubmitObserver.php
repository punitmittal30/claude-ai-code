<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Observer;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Review;
use Pratech\Base\Logger\Logger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\StoreCredit\Helper\Config as StoreCreditConfig;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;
use Pratech\StoreCredit\Model\CreditPointsFactory;

/**
 * Observer to update store credit after First review submit.
 */
class ReviewSubmitObserver implements ObserverInterface
{
    /**
     * @param Logger $apiLogger
     * @param CreditPointsFactory $creditPointsFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreCreditHelper $storeCreditHelper
     * @param CustomerRedisCache $customerRedisCache
     * @param StoreCreditConfig $storeCreditConfig
     */
    public function __construct(
        private Logger                      $apiLogger,
        private CreditPointsFactory         $creditPointsFactory,
        private CustomerRepositoryInterface $customerRepository,
        private StoreCreditHelper           $storeCreditHelper,
        private CustomerRedisCache          $customerRedisCache,
        private StoreCreditConfig           $storeCreditConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /**
         * @var Review $review
         */
        $review = $observer->getEvent()->getObject();
        if ($this->storeCreditConfig->isFirstReviewCashbackEnabled()) {
            if ($review) {
                try {
                    $this->processReview($review);

                    $this->customerRedisCache->deleteCustomerStoreCreditTransactions($review->getCustomerId());
                } catch (Exception $exception) {
                    $this->apiLogger->error("Error processing store credit for review: "
                        . $exception->getMessage());
                }
            }
        }
    }

    /**
     * Process Review
     *
     * @param Review $review
     * @return void
     */
    private function processReview(Review $review): void
    {
        $customerId = $review->getCustomerId();

        if ($customerId) {
            try {
                $customerData = $this->customerRepository->getById($customerId);

                $isReviewed = $customerData->getCustomAttribute('is_reviewed')
                    ? $customerData->getCustomAttribute('is_reviewed')->getValue()
                    : 0;

                if ($isReviewed == 0) {
                    $this->updateStoreCreditAfterFirstReview($review, $customerData);
                } elseif ($isReviewed == 1 && $review->getStatusId() == 1) {
                    $this->processPendingCreditPoints($customerId);
                }
            } catch (Exception $exception) {
                $this->apiLogger->error(
                    "Store Credit | Customer Review | Unable to add cashback for customer
                    review for customer id : " . $customerId . " | " . $exception->getMessage()
                );
            }

        }
    }

    /**
     * Update Store Credit After First Review
     *
     * @param Review $review
     * @param CustomerInterface $customerData
     * @return void
     */
    private function updateStoreCreditAfterFirstReview(Review $review, CustomerInterface $customerData): void
    {
        try {
            $this->creditStoreCreditPoints($review);
            $customerData->setCustomAttribute('is_reviewed', 1);
            $this->customerRepository->save($customerData);
        } catch (Exception $exception) {
            $this->apiLogger->error("Store Credit | Customer Review | Unable to update customer attribute
                is_reviewed after crediting cashback for customer id #" . $customerData->getId() . " | " .
                $exception->getMessage());
        }
    }

    /**
     * Credit Store Credit Points
     *
     * @param Review $review
     * @return void
     */
    private function creditStoreCreditPoints(Review $review): void
    {
        $customerId = $review->getCustomerId();
        $fixedAmount = $this->storeCreditConfig->getFirstReviewCashbackAmount();
        $msg = $this->storeCreditConfig->getAdditionalInfoForFirstReviewCashback();
        $this->creditPointsFactory->create()->setCustomerId($customerId)
            ->setOrderId(" ")
            ->setShipmentId(" ")
            ->setCreditPoints($fixedAmount)
            ->setCreditedStatus(2)
            ->setAdditionalInfo(str_replace("%s", $review->getReviewId(), $msg))
            ->save();
    }

    /**
     * Process Pending Credit Points
     *
     * @param int $customerId
     * @return void
     */
    private function processPendingCreditPoints(int $customerId): void
    {
        $creditPoints = $this->creditPointsFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customerId);

        foreach ($creditPoints as $creditPoint) {
            if ($creditPoint->getCreditedStatus() == 2) {
                $this->storeCreditHelper->addStoreCredit(
                    $creditPoint->getCustomerId(),
                    $creditPoint->getCreditPoints(),
                    $creditPoint->getAdditionalInfo() ?? '',
                    [
                        'event_name' => 'review_approved'
                    ]
                );
                $creditPointsModel = $this->creditPointsFactory->create();
                $creditPointsModel->load($creditPoint->getStorecreditId())
                    ->setCreditedStatus(1)
                    ->save();
            }
        }
    }
}
