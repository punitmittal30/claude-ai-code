<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Model\Service;

use Exception;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Api\PaymentFailuresInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\OrderNotifier;
use Pratech\Base\Logger\RestApiLogger;
use Pratech\Refund\Helper\Data as RefundHelper;
use Psr\Log\LoggerInterface;

/**
 * Overriding Order Service Class of Default Magento
 */
class OrderService extends \Magento\Sales\Model\Service\OrderService
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param OrderNotifier $notifier
     * @param ManagerInterface $eventManager
     * @param OrderCommentSender $orderCommentSender
     * @param PaymentFailuresInterface $paymentFailures
     * @param LoggerInterface $logger
     * @param RestApiLogger $apiLogger
     * @param RefundHelper $refundHelper
     * @param State $appState
     */
    public function __construct(
        OrderRepositoryInterface              $orderRepository,
        OrderStatusHistoryRepositoryInterface $historyRepository,
        SearchCriteriaBuilder                 $criteriaBuilder,
        FilterBuilder                         $filterBuilder,
        OrderNotifier                         $notifier,
        ManagerInterface                      $eventManager,
        OrderCommentSender                    $orderCommentSender,
        PaymentFailuresInterface              $paymentFailures,
        LoggerInterface                       $logger,
        private RestApiLogger                 $apiLogger,
        private RefundHelper                  $refundHelper,
        private State                         $appState
    ) {
        parent::__construct(
            $orderRepository,
            $historyRepository,
            $criteriaBuilder,
            $filterBuilder,
            $notifier,
            $eventManager,
            $orderCommentSender,
            $paymentFailures,
            $logger
        );
    }

    /**
     * Override Magento's Default Order cancel
     *
     * @param int $id
     * @return bool
     */
    public function cancel($id): bool
    {
        try {
            $request = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Webapi\Rest\Request::class);
            $headers = $request->getHeaders()->toArray();
            $params = $request->getParams();
            $content = $request->getContent();
            $logData = [
                'endpoint' => '/V1/orders/' . $id . '/cancel',
                'method' => $request->getMethod(),
                'order_id' => $id,
                'headers' => json_encode($headers),
                'params' => json_encode($params),
                'content' => $content,
                'request_time' => date('Y-m-d H:i:s')
            ];
            $this->apiLogger->info('Order Cancel Request Body: ' . json_encode($logData));

            $cancellableStatus = ['processing', 'packed'];
            $order = $this->orderRepository->get($id);
            if ($order->canCancel() && in_array($order->getStatus(), $cancellableStatus)) {
                $order->cancel();
                if ($this->appState->getAreaCode() == "webapi_rest") {
                    $order->addCommentToStatusHistory("Vinculum: Order Canceled");
                }
                $this->orderRepository->save($order);

                // Refund in case of prepaid order
                if ($this->refundHelper->isRefundEligibleForFullOrder($order)) {
                    $this->refundHelper->triggerRefundForFullOrder($order, 'INITIATE_REFUND');
                }

                return true;
            }
        } catch (Exception $e) {
            $this->apiLogger->error(
                "Cancel Order Issue From Core API(Vinculum) | Order Entity ID " . $id
                . " | " . $e->getMessage()
            );
        }
        return false;
    }
}
