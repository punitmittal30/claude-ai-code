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
use Pratech\Warehouse\Api\WarehouseRepositoryInterface;
use Pratech\Warehouse\Model\WarehouseFactory;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action
{
    /**
     * @param Context $context
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param WarehouseFactory $warehouseFactory
     */
    public function __construct(
        Context                                $context,
        protected WarehouseRepositoryInterface $warehouseRepository,
        protected WarehouseFactory             $warehouseFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $id = $this->getRequest()->getParam('warehouse_id');
                if ($id) {
                    $model = $this->warehouseRepository->getById($id);
                } else {
                    unset($data['warehouse_id']);
                    $model = $this->warehouseFactory->create();
                }

                $data['pincode'] = (int)$data['pincode'];
                $model->setData($data);
                $this->warehouseRepository->save($model);
                $this->messageManager->addSuccessMessage(__('The warehouse has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['warehouse_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['warehouse_id' => $id]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
