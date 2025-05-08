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

namespace Pratech\Return\Controller\Adminhtml\Request\Pending;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory;
use Pratech\Return\Model\VinculumIntegration;

class MassProcessRequest extends Action
{
    public const ADMIN_RESOURCE = 'Pratech_Return::request_save';

    /**
     * Mass Process Return Request Constructor
     *
     * @param Action\Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RequestRepositoryInterface $repository
     * @param VinculumIntegration $vinculumIntegration
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        Action\Context                     $context,
        private Filter                     $filter,
        private CollectionFactory          $collectionFactory,
        private RequestRepositoryInterface $repository,
        private VinculumIntegration        $vinculumIntegration,
        private OrderReturnHelper          $orderReturnHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Mass Process Return Request
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $processedCount = 0;
        $errorCount = 0;

        foreach ($collection as $request) {
            try {
                $response = $this->vinculumIntegration->processReturnRequest($request);
                if (isset($response['requestStatus']) && $response['requestStatus']['status'] === 'Success') {
                    $statusId = $this->orderReturnHelper->getStatusId('return_initiated');
                    if ($statusId) {
                        $request->setStatus($statusId);
                    }
                    $request->setIsProcessed(1);
                    $request->setVinReturnId($response['requestStatus']['outputKey'] ?? null);
                    $this->repository->save($request);
                    $processedCount++;
                } else {
                    $errorCount++;
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $errorCount++;
            }
        }

        if ($processedCount) {
            $this->messageManager->addSuccessMessage(__(
                "%1 return requests processed successfully.",
                $processedCount
            ));
        }
        if ($errorCount) {
            $this->messageManager->addErrorMessage(__("%1 return requests failed to process.", $errorCount));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
