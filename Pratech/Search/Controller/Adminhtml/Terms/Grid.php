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

namespace Pratech\Search\Controller\Adminhtml\Terms;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class Grid extends \Pratech\Search\Controller\Adminhtml\SearchTerms
{

    /**
     * Grid Constructor
     *
     * @param Context       $context
     * @param RawFactory    $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        protected Context $context,
        protected RawFactory $resultRawFactory,
        protected LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Grid Action
     * Display list of products related to current search term
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $searchTerm = $this->_initSearchTerm(true);
        if (!$searchTerm) {
            /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
*/
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('pratech_search/*/', ['_current' => true, 'id' => null]);
        }
        /**
 * @var \Magento\Framework\Controller\Result\Raw $resultRaw
*/
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $this->layoutFactory->create()->createBlock(
                \Pratech\Search\Block\Adminhtml\Terms\Tab\Product::class,
                'search_term.product.grid'
            )->toHtml()
        );
    }
}
