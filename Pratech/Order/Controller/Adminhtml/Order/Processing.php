<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Order\Controller\Adminhtml\Order;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;

class Processing extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * Order Process Constructor
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param AuthSession $authSession
     */
    public function __construct(
        Action\Context                   $context,
        private OrderRepositoryInterface $orderRepository,
        private AuthSession              $authSession
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Method
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $order = $this->_initOrder();
        if ($order) {
            try {
                $adminUsername = $this->getCurrentUser()->getUsername();
                $rzpOrderId = "created_by_" . $adminUsername;
                $rzpPaymentId = "created_by_" . $adminUsername;

                $order->setStatus(SalesOrder::STATE_PROCESSING)
                    ->setState(SalesOrder::STATE_PROCESSING);
                $order->addCommentToStatusHistory(
                    "API : Processing(processing)"
                    . " | Source : " . "ADMIN"
                    . " | Razorpay Order ID : " . $rzpOrderId
                    . " | Razorpay Payment ID : " . $rzpPaymentId
                );
                $payment = $this->setOrderPayment($order, $order->getPayment());
                $order->setPayment($payment);
                $order->setIsConfirmed(1);
                $order->setRzpOrderId($rzpOrderId);
                $order->setRzpPaymentId($rzpPaymentId);
                $this->orderRepository->save($order);

                $this->messageManager->addSuccessMessage(__('You have processed the order.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not processed the order.'));
            }
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('sales/order/view');
        return $resultRedirect;
    }

    /**
     * Initialize order model instance
     *
     * @return OrderInterface|false
     */
    private function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            return false;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            return false;
        }
        return $order;
    }

    /**
     * Get current admin user
     *
     * @return object
     */
    private function getCurrentUser()
    {
        return $this->authSession->getUser();
    }

    /**
     * Update Payment Details in sales_order_payment table.
     *
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return OrderPaymentInterface
     */
    private function setOrderPayment(OrderInterface $order, OrderPaymentInterface $payment): OrderPaymentInterface
    {
        $payment->setAmountPaid($order->getPayment()->getAmountOrdered());
        $payment->setBaseAmountPaid($order->getPayment()->getBaseAmountOrdered());
        $payment->setBaseAmountPaidOnline($order->getPayment()->getBaseAmountPaidOnline());
        return $payment;
    }
}
