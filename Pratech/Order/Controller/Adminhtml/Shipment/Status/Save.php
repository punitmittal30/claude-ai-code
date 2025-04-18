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
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttributes;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Order\Model\ShipmentStatusFactory;

/**
 * Shipment Status Save Controller
 */
class Save extends Action
{

    /**
     * Save constructor
     *
     * @param Action\Context        $context
     * @param ShipmentStatusFactory $shipmentStatusFactory
     * @param RedirectFactory       $redirectFactory
     * @param Session               $session
     * @param EavAttributes         $eavAttributes
     */
    public function __construct(
        protected Action\Context  $context,
        protected ShipmentStatusFactory   $shipmentStatusFactory,
        protected RedirectFactory $redirectFactory,
        protected Session         $session,
        protected EavAttributes $eavAttributes
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $id = $data['status_id'];
                $shipmentStatus = $this->shipmentStatusFactory->create()->load($id);

                $data = array_filter(
                    $data,
                    function ($value) {
                        return $value !== '';
                    }
                );
                if (isset($data['attribute_code'])) {
                    $attributeDetails = $this->eavAttributes->loadByCode('catalog_product', $data['attribute_code']);
                    $data['attribute_id'] = $attributeDetails->getId();
                    $data['attribute_name'] = $attributeDetails->getDefaultFrontendLabel();
                }
                $shipmentStatus->setData($data);
                $shipmentStatus->save();
                $this->_eventManager->dispatch(
                    'shipment_status_controller_save_after',
                    ['shipmentStatus' => $shipmentStatus]
                );
                $this->messageManager->addSuccessMessage(__('Shipment Status successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirectFactory->create()->setPath(
                        '*/*/edit',
                        ['id' => $shipmentStatus->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
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
        return $this->_authorization->isAllowed('Magento_Sales::shipment_statuses');
    }
}
