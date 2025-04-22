<?php

namespace Pratech\Recurring\Controller\Adminhtml\Subscription;

use Magento\Framework\Controller\ResultFactory;

/**
 * Recurring Adminhtml Subscription Controller
 */
class Edit extends \Pratech\Recurring\Controller\Adminhtml\Subscription
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Pratech_Recurring::Subscription';
    
    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $model = $this->subscriptionFactory->create();
        $data = $this->backendSession->getFormData(true);

        if (isset($params['id']) && $params['id']) {
            $model->load($params['id']);
        }
        if (!empty($data)) {
            $model->setData($data);
        }

        /* Subscriptions data */
        $this->registry->register('subscription_data', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription Details'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('Subscription Details')
        );

        $resultPage->addBreadcrumb(
            __('Subscription Details'),
            __('Subscription Details')
        );
        return $resultPage;
    }
}
