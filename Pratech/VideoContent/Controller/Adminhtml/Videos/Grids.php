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

namespace Pratech\VideoContent\Controller\Adminhtml\Videos;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Pratech\VideoContent\Block\Adminhtml\Video\Tab\ProductGrid;

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
                'video.custom.tab.productgrid'
            )->toHtml()
        );
    }
}
