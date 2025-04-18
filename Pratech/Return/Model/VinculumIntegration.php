<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Base\Logger\Logger;
use Pratech\Return\Api\Data\ReasonInterfaceFactory;
use Pratech\Return\Model\Request\Request;

/**
 * Vinculum Integration Class to call click post api
 */
class VinculumIntegration
{
    /**
     * Vinculum Get Return Track No Endpoint
     */
    public const VINCULUM_GET_RETURN_TRACK_NO_ENDPOINT = '/RestWS/api/eretail/v1/order/orderreturn';

    /**
     * Vinculum Order Return Host
     */
    public const VINCULUM_ORDER_RETURN_API_HOST = 'return/return/host';

    /**
     * Vinculum Create Order Return Endpoint
     */
    public const VINCULUM_CREATE_ORDER_RETURN_API_ENDPOINT = '/RestWS/api/eretail/v1/order/return';

    /**
     * Vinculum Update Order Return Endpoint
     */
    public const VINCULUM_UPDATE_ORDER_RETURN_API_ENDPOINT = '/RestWS/api/eretail/v1/order/orderreturn';

    /**
     * Vinculum Integration Owner
     */
    public const VINCULUM_OWNER = 'return/return/owner';

    /**
     * Vinculum Integration Key
     */
    public const VINCULUM_KEY = 'return/return/key';


    /**
     * Vinculum Integration Constructor
     *
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param BaseHelper $baseHelper
     * @param ReasonInterfaceFactory $reasonInterfaceFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Logger $apiLogger
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        private EncryptorInterface          $encryptor,
        private ScopeConfigInterface        $scopeConfig,
        private Curl                        $curl,
        private BaseHelper                  $baseHelper,
        private ReasonInterfaceFactory      $reasonInterfaceFactory,
        private OrderRepositoryInterface    $orderRepository,
        private ShipmentRepositoryInterface $shipmentRepository,
        private Logger                      $apiLogger,
        private JsonHelper                  $jsonHelper
    ) {
    }

    /**
     * Process Return Request On Vinculum
     *
     * @param  Request $request
     * @return array
     * @throws LocalizedException
     */
    public function processReturnRequest(Request $request): array
    {
        $order = $this->orderRepository->get($request->getOrderId());

        $trackingNumber = $this->getTrackingNumbersByShipmentId($request->getShipmentId());
        $shippingAddress = $order->getShippingAddress();
        $items = $request->getRequestItems();
        $itemsData = [];
        foreach ($items as $item) {
            if ($item->getItemStatus() != 1) {
                continue;
            }
            foreach ($order->getItems() as $orderItem) {
                if ($orderItem->getItemId() == $item->getOrderItemId()) {
                    break;
                }
            }
            $reason = $this->reasonInterfaceFactory->create()->load($item->getReasonId());
            $itemsData[] = [
                "sku" => $orderItem->getSku(),
                "return_qty" => $item->getRequestQty(),
                "return_reason" => $reason->getTitle(),
            ];
        }
        $body = [
            'orderReturn' => [
                [
                    'requestType' => 'Request',
                    'returnType' => 'Delivered Return',
                    'order_location' => 'W01',
                    'transporter' => '1002',
                    'order_no' => $order->getIncrementId(),
                    'tracking_no' => $trackingNumber[0],
                    'status' => 'Confirmed',
                    'return_date' => $this->baseHelper
                        ->getDateTimeBasedOnTimezone($request->getCreatedAt(), 'd/m/yy H:i:s'),
                    'customer_name' => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
                    'customer_address1' => implode(', ', $shippingAddress->getStreet()),
                    'customer_country' => 'India',
                    'customer_state' => $shippingAddress->getRegion(),
                    'customer_city' => $shippingAddress->getCity(),
                    'customer_pincode' => $shippingAddress->getPostcode(),
                    'customer_email' => $shippingAddress->getEmail(),
                    'remarks' => 'DamagedGood',
                    'items' => $itemsData
                ]
            ]
        ];

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];

        // Set request body
        $data = [
            'ApiOwner' => $this->getOwner(),
            'ApiKey' => $this->getKey(),
            'RequestBody' => $this->jsonHelper->jsonEncode($body)
        ];
        $url = $this->getCreateReturnApiUrl();

        $this->apiLogger->error('Order Return request data: ', $data);

        if (null != $url) {

            $this->curl->setHeaders($headers);
            $this->curl->post($url, http_build_query($data));
            $response = $this->jsonHelper->jsonDecode($this->curl->getBody(), true);

            $this->apiLogger->error('Order Return Response: ', $response);

            return $response;
        }
        return [];
    }

    /**
     * Get tracking numbers by shipment ID
     *
     * @param  int $shipmentId
     * @return array
     */
    public function getTrackingNumbersByShipmentId(int $shipmentId): array
    {
        // Load the shipment by ID
        $shipment = $this->shipmentRepository->get($shipmentId);

        // Initialize array to hold tracking numbers
        $trackingNumbers = [];

        // Get the tracks associated with the shipment
        foreach ($shipment->getTracks() as $track) {
            $trackingNumbers[] = $track->getTrackNumber(); // Retrieve tracking number
        }

        return $trackingNumbers;
    }

    /**
     * Get Vinculum Owner
     *
     * @return string
     */
    public function getOwner(): string
    {
        $owner = $this->scopeConfig->getValue(
            self::VINCULUM_OWNER,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($owner);
    }

    /**
     * Get Vinculum Key
     *
     * @return string
     */
    public function getKey(): string
    {
        $key = $this->scopeConfig->getValue(
            self::VINCULUM_KEY,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($key);
    }

    /**
     * Get Create Order Return Api Url
     *
     * @return string|null
     */
    public function getCreateReturnApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::VINCULUM_ORDER_RETURN_API_HOST,
            ScopeInterface::SCOPE_STORE
        ) . self::VINCULUM_CREATE_ORDER_RETURN_API_ENDPOINT;
    }

    /**
     * Update Return Request From Vinculum
     *
     * @param  Request $request
     * @return array
     */
    public function updateReturnRequest(Request $request): array
    {

        $body[] = [
            "order_no" => ["NHW1600256"],
        ];

        $url = $this->getUpdateReturnApiUrl();

        if (null != $url) {
            $urlParams = [
                "apiOwner" => $this->getOwner(),
                "apiKey" => $this->getKey()
            ];

            $apiUrl = $url . '?' . http_build_query($urlParams);

            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($apiUrl, json_encode($body));
            return json_decode($this->curl->getBody(), true);
        }
        return [];
    }

    /**
     * Get Update Order Return Api Url
     *
     * @return string|null
     */
    private function getUpdateReturnApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::VINCULUM_ORDER_RETURN_API_HOST,
            ScopeInterface::SCOPE_STORE
        ) . self::VINCULUM_UPDATE_ORDER_RETURN_API_ENDPOINT;
    }

    /**
     * Get Return Track No Api Url
     *
     * @return string|null
     */
    public function getReturnTrackNoApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::VINCULUM_ORDER_RETURN_API_HOST,
            ScopeInterface::SCOPE_STORE
        ) . self::VINCULUM_GET_RETURN_TRACK_NO_ENDPOINT;
    }
}
