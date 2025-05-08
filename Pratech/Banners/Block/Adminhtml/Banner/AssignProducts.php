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

namespace Pratech\Banners\Block\Adminhtml\Banner;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Pratech\Banners\Block\Adminhtml\Banner\Tab\ProductGrid;
use Pratech\Banners\Model\ResourceModel\Banner;

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
    protected $_template = 'banner/assign_products.phtml';

    /**
     * @var Product
     */
    protected $blockGrid;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $productFactory
     * @param Banner $banner
     * @param array $data
     */
    public function __construct(
        Context                     $context,
        protected Registry          $registry,
        protected EncoderInterface  $jsonEncoder,
        protected CollectionFactory $productFactory,
        protected Banner            $banner,
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
                'banner.product.grid'
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
        $bannerId = $this->getRequest()->getParam('id');
        if ($bannerId) {
            $products = [];
            $productCollection = $this->banner->getProductsAndPosition($bannerId);
            if (!empty($productCollection)) {
                foreach ($productCollection as $key => $value) {
                    $products[$key] = $value;
                }
                return $this->jsonEncoder->encode($products);
            }
        }
        return '{}';
    }

    /**
     * Get Item
     *
     * @return mixed|null
     */
    public function getItem(): mixed
    {
        return $this->registry->registry('banner_product');
    }
}
