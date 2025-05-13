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

namespace Pratech\Banners\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Pratech\Banners\Block\Adminhtml\Banner\Tab\ProductGrid;

class Grids extends Action
{
    /**
     * @param Context $context
     * @param Rawfactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context                 $context,
        protected Rawfactory    $resultRawFactory,
        protected LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Raw
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $this->layoutFactory->create()->createBlock(
                ProductGrid::class,
                'banner.custom.tab.productgrid'
            )->toHtml()
        );
    }
}
