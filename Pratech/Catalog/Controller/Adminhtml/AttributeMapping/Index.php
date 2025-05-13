<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Controller\Adminhtml\AttributeMapping;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * Index constructor
     *
     * @param Action\Context $context
     * @param PageFactory    $pageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory    $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $pageFactory = $this->pageFactory->create();
        $pageFactory->setActiveMenu('Pratech_Catalog::attributes_mapping');
        $pageFactory->getConfig()->getTitle()->prepend(
            __('Attributes Mapping')
        );
        return $pageFactory;
    }
}
