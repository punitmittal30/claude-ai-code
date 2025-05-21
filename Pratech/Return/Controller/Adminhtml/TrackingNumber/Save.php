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

namespace Pratech\Return\Controller\Adminhtml\TrackingNumber;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Pratech\Return\Api\RequestRepositoryInterface;

class Save extends Action
{
    /**
     * Save Tracking Number Constructor
     *
     * @param Action\Context $context
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        Action\Context                     $context,
        private RequestRepositoryInterface $requestRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Save Tracking Number
     */
    public function execute()
    {
        /**
         * @var Json $response
         */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $number = $this->getRequest()->getParam('number');
        $code = $this->getRequest()->getParam('code');
        $requestId = $this->getRequest()->getParam('request_id');
        if ($requestId && $number && $code) {
            $tracking = $this->requestRepository->getEmptyTrackingModel();

            $tracking->setTrackingCode($code)
                ->setTrackingNumber($number)
                ->setIsCustomer(false)
                ->setRequestId($requestId);
            try {
                $this->requestRepository->saveTracking($tracking);
            } catch (Exception $e) {
                return $response->setData([]);
            }

            return $response->setData(['success' => true, 'id' => $tracking->getTrackingId()]);
        }

        return $response->setData([]);
    }
}
