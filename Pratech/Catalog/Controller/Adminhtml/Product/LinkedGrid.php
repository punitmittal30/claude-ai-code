<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Catalog\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutFactory;
use Pratech\Catalog\Block\Adminhtml\Product\Tab\LinkedProductGrid;

class LinkedGrid extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * Constructor
     *
     * @param Context       $context
     * @param RawFactory    $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context                 $context,
        protected RawFactory    $resultRawFactory,
        protected LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Grid Action
     * Display linked products grid
     *
     * @return Raw
     */
    public function execute(): Raw
    {
        /**
         * @var Raw $resultRaw
         */
        $resultRaw = $this->resultRawFactory->create();

        /**
         * @var Layout $layout
         */
        $layout = $this->layoutFactory->create();

        /**
         * @var LinkedProductGrid $block
         */
        $block = $layout->createBlock(
            LinkedProductGrid::class,
            'linked.product.grid'
        );

        return $resultRaw->setContents($block->toHtml());
    }
}
