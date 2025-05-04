<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Controller\Adminhtml\Order\PartialCancel;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Model\Order;
use Pratech\Order\Helper\Order as OrderHelper;

class Submit extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::cancel';

    /**
     * Constructor
     *
     * @param Action\Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Action\Context $context,
        protected ForwardFactory $resultForwardFactory,
        protected OrderHelper $orderHelper
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * Submit Partial Order cancellation
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPost('partialcancel');
        try {
            $orderId = $this->getRequest()->getParam('order_id');
            if ($orderId) {
                if (empty($data['items'])) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('No item(s) selected for cancellation.')
                    );
                }

                $this->orderHelper->processPartialOrderCancellation($orderId, $data);

                $this->messageManager->addSuccessMessage(__('You submitted the partial cancellation.'));
                $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
                return $resultRedirect;
            } else {
                $resultForward = $this->resultForwardFactory->create();
                $resultForward->forward('noroute');
                return $resultForward;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_getSession()->setFormData($data);
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->messageManager->addErrorMessage(__('We can\'t submit the partial cancellation right now.'));
        }
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
        return $resultRedirect;
    }
}
