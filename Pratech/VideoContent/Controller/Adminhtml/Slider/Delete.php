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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\VideoContent\Model\SliderFactory;

/**
 * Class Delete
 * Delete controller to delete a slider.
 */
class Delete extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param SliderFactory $sliderFactory
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Action\Context $context,
        protected SliderFactory $sliderFactory,
        RedirectFactory $redirectFactory
    ) {
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
            $slider = $this->sliderFactory->create()->load($id);
            try {
                $slider->delete();
                // $this->_eventManager->dispatch('video_slider_controller_delete_after', ['slider' => $slider]);
                $this->messageManager->addSuccessMessage(__('Carousel has been successfully removed'));
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
        return $this->_authorization->isAllowed('Pratech_VideoContent::slider');
    }
}
