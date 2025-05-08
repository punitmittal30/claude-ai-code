<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\StoreCredit\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;

abstract class PendingOrder extends Action
{

    public const ADMIN_RESOURCE = 'Pratech_StoreCredit::pendingorder';

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context   $context,
        protected Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param Page $resultPage
     * @return Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Pratech'), __('Pratech'))
            ->addBreadcrumb(__('Pending Order Credits'), __('Pending Order Credits'));
        return $resultPage;
    }
}
