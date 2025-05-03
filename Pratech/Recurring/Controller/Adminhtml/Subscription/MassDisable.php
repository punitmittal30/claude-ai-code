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

use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;

/**
 * Recurring Adminhtml Plans massDelete Controller
 */
class MassDisable extends \Pratech\Recurring\Controller\Adminhtml\Subscription
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Pratech_Recurring::Subscription';
    
    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $subscriptionModel = $this->subscriptionFactory->create();
        $collection  = $this->massFilter->getCollection($subscriptionModel->getCollection());
        $errorFlag   = 1;
        foreach ($collection as $model) {
            if ($model->getStatus() == SubscriptionStatus::DISABLED) {
                $this->messageManager->addSuccessMessage(
                    __('Subscription(s) already Unsubscribed')
                );
            } else {
                $this->setStatus($model, SubscriptionStatus::DISABLED);
                $errorFlag  = 0;
            }
        }
        if ($errorFlag == 0) {
            $this->messageManager->addSuccessMessage(
                __('Subscription(s) Unsubscribed successfully.')
            );
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}
