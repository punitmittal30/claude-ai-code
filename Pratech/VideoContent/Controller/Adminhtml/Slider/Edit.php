<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Controller\Adminhtml\Slider;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Pratech\VideoContent\Controller\Adminhtml\Slider;

/**
 * Edit controller to manage edit action in slide management.
 */
class Edit extends Slider
{
    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('slider_id');
        $model = $this->_initSlider($id);
        if ($id) {
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(
                    __('This carousel is no longer exists.')
                );
                $resultForward = $this->resultRedirectFactory->create();

                return $resultForward->setPath('*/*/');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->coreRegistry->register('video_slider', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Pratech_VideoContent::slider');
        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Carousel');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Carousel');
        }

        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_VideoContent::slider');
    }
}
