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
declare(strict_types=1);

namespace Pratech\ProteinCalculator\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;

abstract class Multipliers extends Action
{
    public const ADMIN_CALCULATOR_RESOURCE = 'Pratech_ProteinCalculator::multipliersData';

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
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
    protected function initPage(Page $resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_CALCULATOR_RESOURCE)
            ->addBreadcrumb(__('Pratech'), __('Pratech'))
            ->addBreadcrumb(__('MultipliersData'), __('MultipliersData'));
        return $resultPage;
    }
}
