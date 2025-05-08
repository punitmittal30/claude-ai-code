<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\ReviewRatings\Controller\Adminhtml\Keywords;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Pratech\ReviewRatings\Model\Keywords;

class Edit extends \Pratech\ReviewRatings\Controller\Adminhtml\Keywords
{

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context               $context,
        Registry              $coreRegistry,
        protected PageFactory $resultPageFactory
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
        $model = $this->_objectManager->create(Keywords::class);

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Keywords no longer exists.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->coreRegistry->register('pratech_reviewratings_keywords', $model);

        // 3. Build edit form
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('pratech_reviewratings_keywords_edit');
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Keywords') : __('New Keywords'),
            $id ? __('Edit Keywords') : __('New Keywords')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Keywordss'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId()
            ? __('Edit Keywords %1', $model->getId()) : __('New Keywords'));
        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_ReviewRatings::keywords');
    }
}
