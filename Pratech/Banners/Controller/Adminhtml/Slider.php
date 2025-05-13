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

namespace Pratech\Banners\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Banners\Model\SliderFactory;

abstract class Slider extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var SliderFactory
     */
    protected $sliderFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

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
        Context        $context,
        PageFactory    $resultPageFactory,
        LayoutFactory  $layoutFactory,
        RawFactory     $resultRawFactory,
        ForwardFactory $resultForwardFactory,
        SliderFactory  $sliderFactory,
        Registry       $coreRegistry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->layoutFactory = $layoutFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->sliderFactory = $sliderFactory;
        $this->coreRegistry = $coreRegistry;
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
            'Pratech_Banners::pratech'
        );
    }

    /**
     * Initialize and return banner object
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
