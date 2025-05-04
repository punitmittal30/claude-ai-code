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

namespace Pratech\Warehouse\Controller\Adminhtml\Sla;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Pratech\Warehouse\Api\WarehouseSlaRepositoryInterface;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Action
{
    /**
     * @param Context $context
     * @param WarehouseSlaRepositoryInterface $slaRepository
     */
    public function __construct(
        Context                         $context,
        protected WarehouseSlaRepositoryInterface $slaRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete SLA action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('sla_id');
        if ($id) {
            try {
                $this->slaRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The SLA has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['sla_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find an SLA to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check delete permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Warehouse::sla_manage');
    }
}
