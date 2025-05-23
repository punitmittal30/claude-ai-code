<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Recurring\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter as MassFilter;
use Pratech\Recurring\Helper\Recurring as RecurringHelper;
use Pratech\Recurring\Model\SubscriptionFactory;
use Psr\Log\LoggerInterface;

abstract class Subscription extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Pratech_Recurring::pratech_recurring';

    /**
     * Constructor
     *
     * @param Context $context
     * @param BackendSession $backendSession
     * @param FormKeyValidator $formKeyValidator
     * @param Registry $registry
     * @param PageFactory $resultPageFactory
     * @param MassFilter $massFilter
     * @param RecurringHelper $recurringHelper
     * @param SubscriptionFactory $subscriptionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        protected BackendSession $backendSession,
        protected FormKeyValidator $formKeyValidator,
        protected Registry $registry,
        protected PageFactory $resultPageFactory,
        protected MassFilter $massFilter,
        protected RecurringHelper $recurringHelper,
        protected SubscriptionFactory $subscriptionFactory,
        protected LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Pratech'), __('Pratech'))
            ->addBreadcrumb(__('Subscription'), __('Subscription'));
        return $resultPage;
    }
}
