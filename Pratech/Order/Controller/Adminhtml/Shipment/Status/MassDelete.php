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
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory;

/**
 * Mass Delete controller to delete shipment status.
 */
class MassDelete extends Action
{
    /**
     * Mass Delete Constructor
     *
     * @param Filter            $filter
     * @param CollectionFactory $collectionFactory
     * @param RedirectFactory   $redirectFactory
     * @param Action\Context    $context
     */
    public function __construct(
        protected Filter            $filter,
        protected CollectionFactory $collectionFactory,
        protected RedirectFactory   $redirectFactory,
        protected Action\Context    $context
    ) {
        parent::__construct($context);
    }

    /**
     * Shipment Status delete action
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $size = $collection->getSize();

        $collection->walk('delete');
        $this->_eventManager->dispatch(
            'shipment_status_controller_bulk_delete_after',
            ['collection' => $collection]
        );
        $this->messageManager->addSuccessMessage(__("Number of records deleted : " . $size));

        return $this->redirectFactory->create()->setPath('sales/shipment_status/index');
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
