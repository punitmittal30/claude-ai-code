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

namespace Pratech\Warehouse\Controller\Adminhtml\Warehouse;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;

class Delete extends Action
{
    /**
     * @param Context $context
     * @param WarehouseRepositoryInterface $warehouseRepository
     */
    public function __construct(
        Context                      $context,
        protected WarehouseRepositoryInterface $warehouseRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete warehouse action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('warehouse_id');
        if ($id) {
            try {
                $this->warehouseRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The warehouse has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['warehouse_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a warehouse to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check delete permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Warehouse::warehouse_manage');
    }
}
