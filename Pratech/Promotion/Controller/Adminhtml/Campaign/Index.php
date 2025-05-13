<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Controller\Adminhtml\Campaign;

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
     * @param PageFactory $pageFactory
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
        $pageFactory->setActiveMenu('Pratech_Promotion::campaign');
        $pageFactory->getConfig()->getTitle()->prepend(
            __('Campaign')
        );
        return $pageFactory;
    }
}
