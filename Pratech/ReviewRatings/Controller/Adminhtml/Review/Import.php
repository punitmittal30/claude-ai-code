<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Controller\Adminhtml\Review;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManager;
use Pratech\ReviewRatings\Helper\Data as Helper;

class Import extends Action
{
    /**
     * Constant for default rating id.
     */
    protected const DEFAULT_RATING_ID = 1;

    /**
     *  Default entity id.
     */
    protected const DEFAULT_ENTITY_ID = 1;

    /**
     * @var int
     */
    protected $storeID;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param Csv $csvProcessor
     * @param StoreManager $storeManager
     * @param ReviewFactory $reviewFactory
     * @param RatingFactory $ratingFactory
     * @param ProductFactory $productFactory
     * @param Helper $helper
     * @param VoteFactory $voteFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        protected Context                    $context,
        protected UploaderFactory            $uploaderFactory,
        protected Filesystem                 $filesystem,
        protected Csv                        $csvProcessor,
        protected StoreManager               $storeManager,
        protected ReviewFactory              $reviewFactory,
        protected RatingFactory              $ratingFactory,
        protected ProductFactory             $productFactory,
        protected Helper                     $helper,
        protected VoteFactory                $voteFactory,
        protected ProductRepositoryInterface $productRepository,
    ) {
        parent::__construct($context);
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->storeID = $storeManager->getStore()->getId();
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('review/review/index');

        // Get the selected import behavior from the form
        $importBehavior = $this->getRequest()->getParam('import_behavior');

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'reviews_import_file']);
            $uploader->checkAllowedExtension('csv');
            $uploader->skipDbProcessing(true);
            $result = $uploader->save($this->getWorkingDir());

            $this->validateIfHasExtension($result);

            $this->processUpload($result, $importBehavior);
        } catch (Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $resultRedirect;
        }

        return $resultRedirect;
    }

    /**
     * Get the working directory
     *
     * @return string
     */
    public function getWorkingDir(): string
    {
        return $this->varDirectory->getAbsolutePath('importexport/');
    }

    /**
     * Validate if the uploaded file has an extension
     *
     * @param array $result
     * @throws Exception
     */
    public function validateIfHasExtension(array $result): void
    {
        $extension = pathinfo($result['file'], PATHINFO_EXTENSION);
        $uploadedFile = $result['path'] . $result['file'];
        if (!$extension) {
            $this->varDirectory->delete($uploadedFile);
            throw new Exception(__('The file you uploaded has no extension.'));
        }
    }

    /**
     * Validate if the uploaded file have all the required data wrt selected import behaviour
     *
     * @param array $header
     * @param string $importBehavior
     * @param string $uploadedFile
     * @throws Exception
     */
    public function validateDataRequirements(array $header, string $importBehavior, string $uploadedFile): void
    {
        $requiredDataForAdd = ['nickname','rating_value','sku'];
        $requiredDataForDelete = ['review_id'];
        switch ($importBehavior) {
            case 'add':
                $error = (count(array_intersect($header, $requiredDataForAdd)) == count($requiredDataForAdd))
                    ? null
                    : 'Required Values: ' . implode(',', $requiredDataForAdd);
                break;
            case 'update':
            case 'delete':
                $error = count(array_intersect($header, $requiredDataForDelete)) == count($requiredDataForDelete)
                    ? null
                    : 'Required Values: ' . implode(',', $requiredDataForDelete);
                break;
        }
        if ($error !== null) {
            $this->varDirectory->delete($uploadedFile);
            throw new Exception(__('Please provide all the required values. ' . $error));
        }
    }

    /**
     * Process the uploaded filex`
     *
     * @param array $result
     * @param string $importBehavior
     * @throws Exception
     */
    public function processUpload(array $result, string $importBehavior): void
    {
        $importCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        $errorCount = 0;

        $sourceFile = $this->getWorkingDir() . $result['file'];
        $rows = $this->csvProcessor->getData($sourceFile);
        $header = array_shift($rows);

        $this->validateDataRequirements($header, $importBehavior, $sourceFile);
        $productModel = $this->productFactory->create();

        foreach ($rows as $rowData) {
            $reviewData = array_combine($header, $rowData);
            if ($importBehavior != 'delete') {
                if (($importBehavior == 'add' && !isset($reviewData['status']))
                    || (isset($reviewData['status']) && !in_array($reviewData['status'], [1, 2, 3]))
                ) {
                    $reviewData['status'] = 2; // Set default status
                }
                if (isset($reviewData['sku'])) {
                    $sku = $reviewData['sku'];
                    try {
                        $product = $this->productRepository->get($sku);
                    } catch (Exception $e) {
                        $this->messageManager->addError(
                            __("Product with SKU '$sku' not found. Review for SKU $sku not imported.")
                        );
                        $errorCount++;
                        continue;
                    }
                } else {
                    $product = $productModel;
                }
            }
            switch ($importBehavior) {
                case 'add':
                    ($this->importReview($reviewData, $product)) ? $importCount++ : $errorCount++;
                    break;
                case 'update':
                    ($this->updateReview($reviewData, $product)) ? $updateCount++ : $errorCount++;
                    break;
                case 'delete':
                    ($this->deleteReview($reviewData)) ? $deleteCount++ : $errorCount++;
                    break;
            }
        }
        if ($importCount) {
            $this->messageManager->addSuccess(__("$importCount reviews imported successfully."));
        }
        if ($updateCount) {
            $this->messageManager->addSuccess(__("$updateCount reviews updated successfully."));
        }
        if ($deleteCount) {
            $this->messageManager->addSuccess(__("$deleteCount reviews deleted successfully."));
        }
        if ($errorCount) {
            $this->messageManager->addError(__("$errorCount reviews failed to import, update, or delete."));
        }
    }

    /**
     * Import review data
     *
     * @param array $reviewData
     * @param Product $product
     * @return bool
     */
    protected function importReview(array $reviewData, Product $product): bool
    {
        try {
            $review = $this->reviewFactory->create();
            $review->setEntityId(self::DEFAULT_ENTITY_ID)
                ->setEntityPkValue($product->getId())
                ->setNickname($reviewData['nickname'])
                ->setTitle($reviewData['title'] ?? '')
                ->setDetail($reviewData['detail'] ?? '')
                ->setStatusId($reviewData['status']) // Set the provided status
                ->setStoreId($this->storeID)
                ->setStores([$this->storeID])
                ->save();

            $this->setReviewRating($review, $reviewData, $product);
            $review->aggregate();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Set review rating
     *
     * @param Review $review
     * @param array $reviewData
     * @param Product $product
     * @param string $action
     */
    protected function setReviewRating(Review $review, array $reviewData, Product $product, string $action = ''): void
    {
        $ratingId = self::DEFAULT_RATING_ID;
        $ratings[$ratingId] = $this->helper->getVoteOption($ratingId, $reviewData['rating_value']);

        if ($action == 'update') {
            $votes = $this->voteFactory->create()
                ->getResourceCollection()
                ->setReviewFilter($review->getId())
                ->addOptionInfo()
                ->load()
                ->addRatingOptions();
        }

        // Set review rating based on the obtained option ID
        foreach ($ratings as $ratingId => $optionId) {
            if ($action == 'update' && $vote = $votes->getItemByColumnValue('rating_id', $ratingId)) {
                $this->ratingFactory->create()
                    ->setVoteId($vote->getId())
                    ->setReviewId($review->getId())
                    ->updateOptionVote($optionId);
            } else {
                $this->ratingFactory->create()
                    ->setRatingId($ratingId)
                    ->setReviewId($review->getId())
                    ->setCustomerId('')
                    ->addOptionVote($optionId, $product->getId());
            }
        }
    }

    /**
     * Update review
     *
     * @param array $reviewData
     * @param Product $product
     * @return bool
     */
    protected function updateReview(array $reviewData, Product $product): bool
    {
        try {
            $review = $this->reviewFactory->create()->load($reviewData['review_id']);
            if ($review->getId()) {
                $productId = $product->getId() ?: $review->getEntityPkValue();
                // Update the review with new data
                $review->setEntityPkValue($productId)
                    ->setNickname($reviewData['nickname'] ?? $review->getNickname())
                    ->setTitle($reviewData['title'] ?? $review->getTitle())
                    ->setDetail($reviewData['detail'] ?? $review->getDetail())
                    ->setStatusId($reviewData['status'] ?? $review->getStatusId())
                    ->save();

                if (isset($reviewData['rating_value'])) {
                    $this->setReviewRating($review, $reviewData, $product, 'update');
                }
                $review->aggregate();
            }
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Delete review
     *
     * @param array $reviewData
     * @return bool
     */
    protected function deleteReview(array $reviewData): bool
    {
        try {
            $review = $this->reviewFactory->create()
                ->load($reviewData['review_id']);
            if ($review->getId()) {
                $review->delete();
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
