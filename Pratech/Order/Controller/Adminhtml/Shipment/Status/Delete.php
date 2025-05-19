<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Controller\Adminhtml\Shipment\Status;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Order\Model\ShipmentStatusFactory;

/**
 * Delete controller to delete a shipment status.
 */
class Delete extends Action
{

    /**
     * NewAction constructor
     *
     * @param Action\Context        $context
     * @param ShipmentStatusFactory $shipmentStatusFactory
     * @param RedirectFactory       $redirectFactory
     */
    public function __construct(
        protected Action\Context  $context,
        protected ShipmentStatusFactory          $shipmentStatusFactory,
        protected RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $shipmentStatus = $this->shipmentStatusFactory->create()->load($id);
            try {
                $shipmentStatus->delete();
                $this->_eventManager->dispatch(
                    'shipment_status_controller_delete_after',
                    ['shipmentStatus' => $shipmentStatus]
                );
                $this->messageManager->addSuccessMessage(
                    __('Shipment Status has been successfully removed')
                );
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirectFactory->create()
                    ->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::shipment_statuses_delete');
    }
}
