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
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Api\WarehouseSlaRepositoryInterface;
use Pratech\Warehouse\Model\WarehouseSlaFactory;

class Save extends Action
{
    /**
     * @param Context $context
     * @param WarehouseSlaRepositoryInterface $slaRepository
     * @param WarehouseSlaFactory $slaFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context                                   $context,
        protected WarehouseSlaRepositoryInterface $slaRepository,
        protected WarehouseSlaFactory             $slaFactory,
        protected DataPersistorInterface          $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * Save SLA
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            try {
                $id = $this->getRequest()->getParam('sla_id');
                if ($id) {
                    $model = $this->slaRepository->getById($id);
                } else {
                    unset($data['sla_id']);
                    $model = $this->slaFactory->create();
                }

                $model->setData($data);

                // Validate delivery time
                if (!is_numeric($data['delivery_time']) || $data['delivery_time'] <= 0) {
                    throw new LocalizedException(__('Delivery time must be a positive number.'));
                }

                // Validate pincodes
                if (!preg_match('/^\d{6}$/', $data['customer_pincode'])) {
                    throw new LocalizedException(__('Customer pincode must be a 6-digit number.'));
                }

                $this->slaRepository->save($model);
                $this->messageManager->addSuccessMessage(__('The SLA has been saved.'));
                $this->dataPersistor->clear('warehouse_sla');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['sla_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the SLA.'));
            }

            $this->dataPersistor->set('warehouse_sla', $data);
            return $resultRedirect->setPath('*/*/edit', ['sla_id' => $this->getRequest()->getParam('sla_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Warehouse::sla_manage');
    }
}
