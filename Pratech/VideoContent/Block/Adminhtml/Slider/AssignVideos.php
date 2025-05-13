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

namespace Pratech\VideoContent\Block\Adminhtml\Slider;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Pratech\VideoContent\Block\Adminhtml\Slider\Tab\VideoGrid;
use Pratech\VideoContent\Model\ResourceModel\Slider;
use Pratech\VideoContent\Model\ResourceModel\Video\CollectionFactory;

class AssignVideos extends Template
{
    /**
     * @var string
     */
    protected $_template = 'slider/assign_videos.phtml';

    /**
     * @var Product
     */
    protected $blockGrid;

    /**
     * AssignSlides constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $videoFactory
     * @param Slider $slider
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected Registry $registry,
        protected EncoderInterface $jsonEncoder,
        protected CollectionFactory $videoFactory,
        protected Slider $slider,
        array $data = []
    ) {
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
                VideoGrid::class,
                'pratech.slider.video.grid'
            );
        }

        return $this->blockGrid;
    }

    /**
     * Get Video JSON
     *
     * @return string
     */
    public function getVideoJson()
    {
        $sliderId = $this->getRequest()->getParam('slider_id');
        $videos = [];
        if ($sliderId) {
            $videoCollection = $this->slider->getVideos($sliderId);
            if (!empty($videoCollection)) {
                foreach ($videoCollection as $value) {
                    $videos[$value] = $value;
                }
                return $this->jsonEncoder->encode($videos);
            }
        }
        return '{}';
    }
}
