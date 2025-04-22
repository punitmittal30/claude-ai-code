<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
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

