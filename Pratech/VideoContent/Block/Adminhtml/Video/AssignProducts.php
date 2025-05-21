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

namespace Pratech\VideoContent\Block\Adminhtml\Video;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Pratech\VideoContent\Block\Adminhtml\Video\Tab\ProductGrid;
use Pratech\VideoContent\Model\ResourceModel\Video;

/**
 * Assign Products Block Class
 */
class AssignProducts extends Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Pratech_VideoContent::video/assign_products.phtml';

    /**
     * @var Product
     */
    protected $blockGrid;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $productFactory
     * @param Video $video
     * @param array $data
     */
    public function __construct(
        Context                     $context,
        protected Registry          $registry,
        protected EncoderInterface  $jsonEncoder,
        protected CollectionFactory $productFactory,
        protected Video             $video,
        array                       $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Grid HTML
     *
     * @return string
     * @throws LocalizedException
     */
    public function getGridHtml(): string
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Retrieve instance of grid block
     *
     * @return Product|BlockInterface
     * @throws LocalizedException
     */
    public function getBlockGrid(): Product|BlockInterface
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                ProductGrid::class,
                'video.product.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Get Products Json
     *
     * @return string
     */
    public function getProductsJson(): string
    {
        $videoId = $this->getRequest()->getParam('id');
        if ($videoId) {
            $products = [];
            $productCollection = $this->video->getProductsAndPosition($videoId);
            if (!empty($productCollection)) {
                foreach ($productCollection as $key => $value) {
                    $products[$key] = $value;
                }
                return $this->jsonEncoder->encode($products);
            }
        }
        return '{}';
    }
}
