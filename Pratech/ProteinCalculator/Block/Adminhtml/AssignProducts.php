<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\ProteinCalculator\Block\Adminhtml;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Backend\Block\Template\Context;
use Pratech\ProteinCalculator\Model\ResourceModel\Diet\CollectionFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Pratech\ProteinCalculator\Block\Adminhtml\Tab\ProductGrid;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

class AssignProducts extends Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'diet/assign_products.phtml';

    /**
     * @var Product
     */
    protected $blockGrid;

    /**
     * Constructor
     *
     * @param Context           $context
     * @param Registry          $registry
     * @param EncoderInterface  $jsonEncoder
     * @param CollectionFactory $productFactory
     * @param array             $data
     */
    public function __construct(
        Context $context,
        protected Registry $registry,
        protected EncoderInterface $jsonEncoder,
        protected CollectionFactory $productFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
                'diet.product.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Return HTML of grid block
     *
     * @return string
     */
    public function getGridHtml(): string
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Get products JSON
     *
     * @return string
     */
    public function getProductsJson(): string
    {
        $entity_id = $this->getRequest()->getParam('entity_id');
        $productFactory = $this->productFactory->create();
        $productFactory->addFieldToSelect(['product_id']);
        $productFactory->addFieldToFilter('entity_id', ['eq' => $entity_id]);
        $result = [];
        if (!empty($productFactory->getData())) {
            foreach ($productFactory->getData() as $rhProducts) {
                $result[$rhProducts['product_id']] = '';
            }
            return $this->jsonEncoder->encode($result);
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
        return $this->registry->registry('my_item');
    }
}
