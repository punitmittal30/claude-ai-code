<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Controller\Adminhtml\Slider;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

/**
 * Edit controller to manage edit action in slide management.
 */
class Edit extends \Pratech\Banners\Controller\Adminhtml\Slider
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
                    __('This slider is no longer exists.')
                );
                $resultForward = $this->resultRedirectFactory->create();

                return $resultForward->setPath('*/*/');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->coreRegistry->register('slider', $model);

        $resultPage = $this->resultPageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Slider');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Slider');
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
        return $this->_authorization->isAllowed('Pratech_Banners::slider');
    }
}
