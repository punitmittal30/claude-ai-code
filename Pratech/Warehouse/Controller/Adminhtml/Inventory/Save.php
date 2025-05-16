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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Warehouse\Api\WarehouseInventoryRepositoryInterface;
use Pratech\Warehouse\Model\WarehouseInventoryFactory;

class Save extends Action
{
    /**
     * @param Context $context
     * @param WarehouseInventoryRepositoryInterface $inventoryRepository
     * @param WarehouseInventoryFactory $inventoryFactory
     */
    public function __construct(
        Context                                         $context,
        protected WarehouseInventoryRepositoryInterface $inventoryRepository,
        protected WarehouseInventoryFactory             $inventoryFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            try {
                $id = $this->getRequest()->getParam('inventory_id');
                if ($id) {
                    $model = $this->inventoryRepository->getById($id);
                } else {
                    unset($data['inventory_id']);
                    $model = $this->inventoryFactory->create();
                }
                $model->setData($data);
                $this->inventoryRepository->save($model);
                $this->messageManager->addSuccessMessage(__('The inventory has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['inventory_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['inventory_id' => $id]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
