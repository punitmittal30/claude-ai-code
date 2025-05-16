<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Pratech\Catalog\Block\Adminhtml\Product\Tab\LinkedProductGrid;
use Pratech\Catalog\Model\ResourceModel\LinkedProduct;

class LinkedConfigurable extends Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Pratech_Catalog::product/linked_configurable.phtml';

    /**
     * @var LinkedProductGrid
     */
    protected $blockGrid;

    /**
     * LinkedConfigurable constructor.
     *
     * @param Template\Context $context
     * @param Registry         $registry
     * @param EncoderInterface $jsonEncoder
     * @param LinkedProduct    $linkedProductResource
     * @param array            $data
     */
    public function __construct(
        Template\Context           $context,
        protected Registry         $registry,
        protected EncoderInterface $jsonEncoder,
        protected LinkedProduct    $linkedProductResource,
        array                      $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of linked product grid block
     *
     * @return LinkedProductGrid
     * @throws LocalizedException
     */
    public function getBlockGrid(): LinkedProductGrid
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                LinkedProductGrid::class,
                'pratech.product.linked_configurable.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Return HTML of linked product grid block
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Return JSON encoded linked products
     *
     * @return string
     */
    public function getProductsJson(): string
    {
        $product = $this->getProduct();
        if ($product && $product->getId()) {
            $linkedProductIds = $this->linkedProductResource->getLinkedProducts((int)$product->getId());
            if (!empty($linkedProductIds)) {
                $linked = array_combine($linkedProductIds, $linkedProductIds);
                return $this->jsonEncoder->encode($linked);
            }
        }
        return '{}';
    }

    /**
     * Retrieve current product instance
     *
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }
}
