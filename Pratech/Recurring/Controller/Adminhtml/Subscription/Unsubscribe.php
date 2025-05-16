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
namespace Pratech\Recurring\Controller\Adminhtml\Subscription;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;

/**
 * Pratech Recurring Landing page Index Controller.
 */
class Unsubscribe extends \Pratech\Recurring\Controller\Adminhtml\Subscription
{
    /**
     * Execute
     *
     * @return \Magento\Framework\View\Result\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        
        $postData = $this->getRequest()->getParams();
        $id             = $postData['id'];
        $model          = $this->subscriptionFactory->create();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model->load($id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This record no longer exists.'));
                } else {
                    $this->unsubscribe($model);
                    $this->messageManager->addSuccessMessage(
                        __('This record is Unsubscribed Successfully.')
                    );
                }
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage()  . __METHOD__);
                $this->messageManager->addErrorMessage(__('Something went wrong.'));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage()  . __METHOD__);
                $this->messageManager->addErrorMessage(__('Something went wrong.'));
            }
        }
        $resultRedirect->setPath("pratech_recurring/subscription/edit", ["id" => $id]);
        return $resultRedirect;
    }
    
    /**
     * Unsubscribe function
     *
     * @param object $model
     */
    private function unsubscribe($model)
    {
        $model->setStatus(SubscriptionStatus::CANCELLED)
            ->setValidTill(date('Y-m-d'))
            ->setId($model->getId())
            ->save();
    }
}
