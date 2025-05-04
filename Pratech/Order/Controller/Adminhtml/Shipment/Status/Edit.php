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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttributes;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Order\Model\ShipmentStatus;

/**
 * Edit controller to manage edit action in Shipment Status management.
 */
class Edit extends Action
{

    /**
     * Edit constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param ShipmentStatus $shipmentStatus
     * @param EavAttributes $eavAttributes
     */
    public function __construct(
        protected Action\Context $context,
        protected PageFactory    $pageFactory,
        protected ShipmentStatus         $shipmentStatus,
        protected EavAttributes $eavAttributes
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     * @throws LocalizedException
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $shipmentStatus = $this->shipmentStatus;
        if ($id) {
            $shipmentStatus->load($id);
            if (!$shipmentStatus->getId()) {
                $this->messageManager->addErrorMessage(
                    __('This Shipment Status does not exists')
                );
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('sales/shipment_status/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data) && $data['attribute_code'] != $shipmentStatus->getAttributeCode()) {
            $attributeDetails = $this->eavAttributes
                ->loadByCode('catalog_product', $data["attribute_code"]);
            $data['attribute_id'] = $attributeDetails->getId();
            $data['attribute_name'] = $attributeDetails->getDefaultFrontendLabel();
        }
        if (!empty($data)) {
            $shipmentStatus->setData($data);
        }

        /**
         * @var Page $resultPage
        */
        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Shipment Status');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Shipment Status');
        }

        return $resultPage;
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
