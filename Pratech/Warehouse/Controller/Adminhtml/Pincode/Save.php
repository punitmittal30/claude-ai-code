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

namespace Pratech\Warehouse\Controller\Adminhtml\Pincode;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Warehouse\Api\PincodeRepositoryInterface;
use Pratech\Warehouse\Model\PincodeFactory;

class Save extends Action
{
    /**
     * @param Context $context
     * @param PincodeRepositoryInterface $pincodeRepository
     * @param PincodeFactory $pincodeFactory
     */
    public function __construct(
        Context                              $context,
        protected PincodeRepositoryInterface $pincodeRepository,
        protected PincodeFactory             $pincodeFactory
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
                $id = $this->getRequest()->getParam('entity_id');
                if ($id) {
                    $model = $this->pincodeRepository->getById($id);
                } else {
                    unset($data['entity_id']);
                    $model = $this->pincodeFactory->create();
                }
                $model->setData($data);
                $this->pincodeRepository->save($model);
                $this->messageManager->addSuccessMessage(__('The pincode has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
