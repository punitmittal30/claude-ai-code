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

namespace Pratech\Recurring\Controller\Adminhtml\Subscription;

class Index extends \Pratech\Recurring\Controller\Adminhtml\Subscription
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Pratech_Recurring::Subscription';

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__("All Subscriptions"));
        return $resultPage;
    }
}
