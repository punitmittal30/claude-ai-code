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

namespace Pratech\Banners\Block\Adminhtml\Slider;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Pratech\Banners\Block\Adminhtml\Slider\Tab\BannerGrid;
use Pratech\Banners\Model\ResourceModel\Slider;
use Pratech\Banners\Model\ResourceModel\Banner\CollectionFactory;

class AssignBanners extends Template
{
    /**
     * @var string
     */
    protected $_template = 'slider/assign_banners.phtml';

    /**
     * @var Product
     */
    protected $blockGrid;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var CollectionFactory
     */
    protected $bannerFactory;

    /**
     * @var Slider
     */
    protected $slider;

    /**
     * AssignSlides constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $bannerFactory
     * @param Slider $slider
     * @param array $data
     */
    public function __construct(
        Context           $context,
        Registry          $registry,
        EncoderInterface  $jsonEncoder,
        CollectionFactory $bannerFactory,
        Slider            $slider,
        array             $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        $this->bannerFactory = $bannerFactory;
        $this->slider = $slider;
        parent::__construct($context, $data);
    }

    /**
     * Get Grid HTML
     *
     * @return string
     * @throws LocalizedException
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Retrieve instance of grid block
     *
     * @return BlockInterface
     * @throws LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                BannerGrid::class,
                'pratech.banner.grid'
            );
        }

        return $this->blockGrid;
    }

    /**
     * Get Banner JSON
     *
     * @return string
     */
    public function getBannerJson()
    {
        $sliderId = $this->getRequest()->getParam('slider_id');
        $banners = [];
        if ($sliderId) {
            $bannerCollection = $this->slider->getBanners($sliderId);
            if (!empty($bannerCollection)) {
                foreach ($bannerCollection as $value) {
                    $banners[$value] = $value;
                }
                return $this->jsonEncoder->encode($banners);
            }
        }
        return '{}';
    }

    /**
     * Get Item
     *
     * @return mixed|null
     */
    public function getItem()
    {
        return $this->registry->registry('banner_slide');
    }
}
