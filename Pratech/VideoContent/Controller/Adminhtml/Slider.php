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

namespace Pratech\VideoContent\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Pratech\VideoContent\Model\SliderFactory;

abstract class Slider extends Action
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LayoutFactory $layoutFactory
     * @param RawFactory $resultRawFactory
     * @param ForwardFactory $resultForwardFactory
     * @param SliderFactory $sliderFactory
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        protected LayoutFactory $layoutFactory,
        protected RawFactory $resultRawFactory,
        protected ForwardFactory $resultForwardFactory,
        protected SliderFactory $sliderFactory,
        protected Registry $coreRegistry
    ) {
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        parent::__construct($context);
    }

    /**
     * Check if admin has permissions to visit related pages
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Pratech_VideoContent::slider'
        );
    }

    /**
     * Initialize and return slider object
     *
     * @param int $sliderId
     * @return SliderFactory
     */
    protected function _initSlider($sliderId)
    {
        $model = $this->sliderFactory->create();

        if ($sliderId) {
            $model->load($sliderId);
        }

        return $model;
    }
}
