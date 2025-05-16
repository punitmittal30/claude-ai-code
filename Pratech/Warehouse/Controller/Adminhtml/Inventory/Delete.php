<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Controller\Adminhtml\Inventory;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Warehouse\Api\WarehouseInventoryRepositoryInterface;

class Delete extends Action
{
    /**
     * @param Context $context
     * @param WarehouseInventoryRepositoryInterface $inventoryRepository
     */
    public function __construct(
        Context                                         $context,
        protected WarehouseInventoryRepositoryInterface $inventoryRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete inventory action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('inventory_id');
        if ($id) {
            try {
                $this->inventoryRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The inventory record has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['inventory_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find an inventory record to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check delete permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Warehouse::inventory_manage');
    }
}
