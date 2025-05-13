<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Controller\Adminhtml\VinReturnNumber;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Pratech\Return\Api\RequestRepositoryInterface;
use Pratech\Return\Helper\OrderReturn as OrderReturnHelper;

class Save extends Action
{
    /**
     * Save Return Number Constructor
     *
     * @param Action\Context $context
     * @param RequestRepositoryInterface $requestRepository
     * @param OrderReturnHelper $orderReturnHelper
     */
    public function __construct(
        Action\Context                     $context,
        private RequestRepositoryInterface $requestRepository,
        private OrderReturnHelper          $orderReturnHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Save Tracking Number
     */
    public function execute()
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $number = $this->getRequest()->getParam('number');
        $requestId = $this->getRequest()->getParam('request_id');
        if ($requestId && $number) {
            try {
                $statusId = $this->orderReturnHelper->getStatusId('return_initiated');
                $model = $this->requestRepository->getById($requestId);

                if ($model->getRefundedAmount() == 0) {
                    $this->orderReturnHelper->calculateRefundedAmountAndStoreCredit($model);
                }

                if ($statusId) {
                    $model->setStatus($statusId);
                }
                $model->setVinReturnNumber($number);
                $model->setIsProcessed(1);
                $this->requestRepository->save($model);
            } catch (Exception $e) {
                return $response->setData([]);
            }

            return $response->setData(['success' => true]);
        }

        return $response->setData([]);
    }
}
