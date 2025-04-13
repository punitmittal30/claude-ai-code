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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\Banners\Model\Slider;

/**
 * Class Delete
 * Delete controller to delete a slide from banner management.
 */
class Delete extends Action
{
    /**
     * @var Slider
     */
    protected $slider;

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param Slider $slider
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Action\Context  $context,
        Slider          $slider,
        RedirectFactory $redirectFactory
    ) {
        $this->slider = $slider;
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $this->slider->load($id);
            try {
                $this->slider->delete();
                $this->_eventManager->dispatch('slider_controller_delete_after', ['slider' => $this->slider]);
                $this->messageManager->addSuccessMessage(__('Slide has been successfully removed'));
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirect->setPath('*/*/index');
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
