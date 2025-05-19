<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Block\Adminhtml\Terms;

use Pratech\Search\Model\SearchTermsFactory;

class AssignProducts extends \Magento\Backend\Block\Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Pratech_Search::searchterms/edit/assign_products.phtml';

    /**
     * @var \Pratech\Search\Block\Adminhtml\Terms\Product
     */
    protected $blockGrid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * AssignProducts constructor.
     *
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param \Magento\Framework\Registry              $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array                                    $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        protected SearchTermsFactory $searchTermsFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \Pratech\Search\Block\Adminhtml\Terms\Tab\Product::class,
                'search_term.product.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Return HTML of grid block
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * @return array
     */
    public function getProductsJson()
    {
        if (!empty($this->getSearchTerms())
            && $this->getSearchTerms()->getProductIds()
        ) {
            $productIds = explode(',', $this->getSearchTerms()->getProductIds());
            foreach ($productIds as $id) {
                $IdsArray[$id] = $id;
            }
            return $this->jsonEncoder->encode($IdsArray);
        }
        return '{}';
    }

    /**
     * Retrieve current search term instance
     *
     * @return array|null
     */
    public function getSearchTerms()
    {
        return $this->registry->registry('search_terms');
    }
}
