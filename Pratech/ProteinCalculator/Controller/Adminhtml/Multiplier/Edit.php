<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\ProteinCalculator\Controller\Adminhtml\Multiplier;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Pratech\ProteinCalculator\Model\MultiplierFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Pratech\ProteinCalculator\Controller\Adminhtml\Multipliers;

class Edit extends Multipliers
{
    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param MultiplierFactory $multiplierFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        protected PageFactory $resultPageFactory,
        protected MultiplierFactory $multiplierFactory
    ) {
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('entity_id');
        $model = $this->multiplierFactory->create();

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Multiplier no longer exists.'));
                /** @var ResultRedirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('pratech_protein_multipliers', $model);
        // 3. Build edit form
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('calculator_multiplier_edit');
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Multiplier') : __('New Multiplier'),
            $id ? __('Edit Multiplier') : __('New Multiplier')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Multipliers Data'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId()
            ? __('Edit Multiplier %1', $model->getId()) : __('New Multiplier'));
        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_ProteinCalculator::multipliersData');
    }
}
