<?php
/**
 * Pratech_Refund
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Refund
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Refund\Controller\Adminhtml\Shipment;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Pratech\Refund\Helper\Data;

class Refund extends Action
{

    /**
     * @param Context $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Data $refundHelper
     */
    public function __construct(
        Context                               $context,
        protected ShipmentRepositoryInterface $shipmentRepository,
        protected Data                        $refundHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Method.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');

        try {
            $shipment = $this->shipmentRepository->get($shipmentId);

            $refundedAmount = $this->refundHelper->triggerRefundForRto(
                $shipment,
                $shipment->getOrder(),
                'MAGENTO_RTO_REFUND'
            );

            $this->messageManager->addSuccessMessage(__(
                'Shipment amounting to ' . $refundedAmount . ' has been refunded successfully.'
            ));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Error processing refund: %1', $e->getMessage()));
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/view', ['shipment_id' => $shipmentId]);
    }

    /**
     * Is Allowed Method.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Sales::shipment');
    }
}
