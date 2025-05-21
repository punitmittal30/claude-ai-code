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

namespace Pratech\Order\Model;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Helper\Data;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Model\Data\Response;
use Pratech\Order\Api\ShipmentRepositoryInterface;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory as StatusCollectionFactory;
use Pratech\Order\Model\ResourceModel\ShipmentTrackUpdates\CollectionFactory as TrackUpdatesCollectionFactory;
use Pratech\ReviewRatings\Helper\Data as ReviewHelper;

class ShipmentRepository implements ShipmentRepositoryInterface
{
    /**
     * Constant for SHIPMENT API RESOURCE
     */
    public const SHIPMENT_API_RESOURCE = 'shipment';

    /**
     * Order Repository Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param TrackCollection $trackCollection
     * @param Data $baseHelper
     * @param Response $response
     * @param Logger $apiLogger
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentModifier $shipmentModifier
     * @param ReviewHelper $reviewHelper
     * @param ShipmentTrackUpdatesFactory $shipmentTrackUpdatesFactory
     * @param StatusCollectionFactory $statusCollectionFactory
     */
    public function __construct(
        private OrderRepositoryInterface                       $orderRepository,
        private StoreManagerInterface                          $storeManager,
        private TrackCollection                                $trackCollection,
        private Data                                           $baseHelper,
        private Response                                       $response,
        private Logger                                         $apiLogger,
        private ProductRepositoryInterface                     $productRepositoryInterface,
        private \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        private ShipmentModifier                               $shipmentModifier,
        private ReviewHelper                                   $reviewHelper,
        private ShipmentTrackUpdatesFactory                    $shipmentTrackUpdatesFactory,
        private StatusCollectionFactory                        $statusCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getShipmentDetails(string $trackNumber): array
    {
        $mediaBaseUrl = "";
        $result = [];

        try {
            $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (Exception $exception) {
            $this->apiLogger->error(
                "Error while fetching media url | " . $exception->getMessage() . __METHOD__
            );
        }
        try {
            $trackCollection = $this->trackCollection->addFieldToFilter('track_number', $trackNumber);
            if ($trackCollection->getSize() > 0) {
                $trackItem = $trackCollection->getFirstItem();

                $shipment = $trackItem->getShipment();
                $orderId = $shipment->getOrderId();
                $order = $this->orderRepository->get($orderId);

                $result = $this->baseHelper->getOrderDetails($order);

                $result['carrier_title'] = (!empty($shipment->getAllTracks()[0])
                    && !empty($shipment->getAllTracks()[0]->getTitle())) ?
                    $shipment->getAllTracks()[0]->getTitle()
                    : '';
                foreach ($result['items'] as &$items) {
                    $items['image'] = $mediaBaseUrl . 'catalog/product' . $items['image'];
                    $items['qty'] = (int)$items['qty_ordered'];
                    unset($items['qty_ordered']);
                    unset($items['product_type']);
                    unset($items['original_price']);
                    unset($items['row_total']);
                }
                $result['customer_id'] = $order->getCustomerId() ? $order->getCustomerId() : '';
                $result['shipped_items'] = $this->getShipmentItemsData($shipment);
                $result['is_last_shipment'] = $this->isAllItemsShipped($order);
                $result['shipment_type'] = (count($result['shipped_items']) == count($result['items']))
                    ? 'single' : 'partial';
                $result['estimated_delivery_date'] = $order->getEstimatedDeliveryDate()
                    ? $this->baseHelper->getDateTimeBasedOnTimezone($order->getEstimatedDeliveryDate(), 'M d Y')
                    : '';

            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }

        return $this->response->getResponse(
            200,
            'success',
            self::SHIPMENT_API_RESOURCE,
            $result
        );
    }

    /**
     * Get Shipment Items Data
     *
     * @param Shipment $shipment
     * @return array
     * @throws NoSuchEntityException
     */
    private function getShipmentItemsData(Shipment $shipment): array
    {
        $mediaBaseUrl = "";
        $shipmentItems = [];

        try {
            $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (Exception $exception) {
            $this->apiLogger->error("Error while fetching media url | " . $exception->getMessage() . __METHOD__);
        }
        foreach ($shipment->getAllItems() as $item) {
            $product = $this->productRepositoryInterface->getById($item->getProductId());
            $shipmentItems[] = [
                'name' => $item->getName(),
                'image' => $mediaBaseUrl . 'catalog/product' . $product->getImage(),
                'qty' => (int)$item->getQty(),
                'price' => number_format($item->getPrice(), 2),
                'sku' => $item->getSku(),
                'slug' => $product->getUrlKey(),
                'brand' => $product->getCustomAttribute('brand') ?
                    $this->baseHelper->getProductAttributeLabel(
                        'brand',
                        $product->getCustomAttribute('brand')->getValue()
                    ) : "",
                'replenishment_time' => $product->getCustomAttribute('replenishment_time') ?
                    $product->getCustomAttribute('replenishment_time')->getValue()
                    : "",
                'primary_l1_category' => $product->getCustomAttribute('primary_l1_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l1_category')->getValue()
                    ) : "",
                'primary_l2_category' => $product->getCustomAttribute('primary_l2_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l2_category')->getValue()
                    ) : "",
            ];
        }
        return $shipmentItems;
    }

    /**
     * Get Is All Items Shipped or not
     *
     * @param SalesOrder $order
     * @return boolean
     */
    public function isAllItemsShipped(SalesOrder $order): bool
    {
        $items = $order->getItems();
        foreach ($items as $item) {
            if ($item->getQtyShipped() < $item->getQtyOrdered()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setShipmentDetails(string $trackNumber, string $shipmentStatus): array
    {
        $trackItems = [
            "shipment_id" => "",
            "shipment_status" => "",
            "updated_at" => ""
        ];

        try {
            $trackCollection = $this->trackCollection->addFieldToFilter('track_number', $trackNumber);
            if ($trackCollection->getSize() > 0) {
                $trackItem = $trackCollection->getFirstItem();
                $shipment = $trackItem->getShipment();
                $result = $this->shipmentModifier->updateShipment($shipment->getId(), $shipmentStatus);
                $trackItems = [
                    "shipment_id" => $result->getIncrementId(),
                    "shipment_status" => $result->getShipmentStatus(),
                    "updated_at" => $result->getUpdatedAt()
                ];
            }
        } catch (Exception $exception) {
            $this->apiLogger->error(
                "Unable to track shipment with Tracking Number: " . $trackNumber .
                $exception->getMessage() . __METHOD__
            );
        }

        return $this->response->getResponse(
            200,
            'success',
            self::SHIPMENT_API_RESOURCE,
            $trackItems
        );
    }

    /**
     * @inheritDoc
     */
    public function getShipmentReviewFormData(string $shipmentId): array
    {
        $result = [];

        try {
            $shipment = $this->shipmentRepository->get($shipmentId);
            $orderId = $shipment->getOrderId();
            $order = $this->orderRepository->get($orderId);
            $customerId = $order->getCustomerId() ? $order->getCustomerId() : '';
            $shipmentItems = $this->baseHelper->getShipmentItems($shipment->getItems());
            foreach ($shipmentItems as &$item) {
                $hasCustomerReviewed = $this->reviewHelper->getHasCustomerReviewed($item["slug"], $customerId);
                $item["has_customer_reviewed"] = $hasCustomerReviewed;
                $productReviewKeywords = $this->reviewHelper->getReviewKeywordsForProduct($item["categorization"]);
                $item["review_keywords"] = !empty($productReviewKeywords) ? $productReviewKeywords : null;
            }
            $shipmentReviewKeywords = $this->reviewHelper->getReviewKeywordsForShipment();
            $result["increment_id"] = $shipment->getIncrementId();
            $result["entity_id"] = $shipment->getId();
            $result["is_rated"] = (bool)$shipment->getIsRated();
            $result["rating"] = $shipment->getRating();
            $result["review"] = $shipment->getReview() ?: '';
            $result["keywords"] = $shipment->getKeywords() ?: '';
            $result['customer_id'] = $customerId;
            $result['customer_firstname'] = $order->getCustomerFirstname();
            $result['customer_lastname'] = $order->getCustomerLastname();
            $result["review_keywords"] = !empty($shipmentReviewKeywords) ? $shipmentReviewKeywords : null;
            $result['shipped_items'] = $shipmentItems;
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }

        return $this->response->getResponse(
            200,
            'success',
            self::SHIPMENT_API_RESOURCE,
            $result
        );
    }

    /**
     * @inheritDoc
     */
    public function setShipmentReview(
        string $shipmentId,
        string $nickname,
        int    $customerId,
        array  $productReviewData,
        int    $shipmentRating = null,
        string $shipmentReview = null,
        string $shipmentKeywords = null
    ): array {
        $response = [
            'status' => false,
            'message' => ''
        ];
        if (empty($productReviewData)) {
            throw new InputException(__('Not a valid rating data'));
        }

        $shipment = $this->shipmentRepository->get($shipmentId);
        $isRated = $shipment->getIsRated();
        if ($isRated) {
            throw new Exception(__('You have already given feedback for this shipment.'));
        }

        $response = $this->reviewHelper->setProductReviews($nickname, $customerId, $productReviewData);
        if (!empty($shipmentRating)) {
            $shipment->setRating($shipmentRating);
            $shipment->setIsRated(true);
            if (!empty($shipmentReview)) {
                $shipment->setReview($shipmentReview);
            }
            if (!empty($shipmentKeywords)) {
                $shipment->setKeywords($shipmentKeywords);
            }
            $this->shipmentRepository->save($shipment);
        }

        return $this->response->getResponse(
            200,
            'success',
            self::SHIPMENT_API_RESOURCE,
            $response
        );
    }

    /**
     * @inheritDoc
     */
    public function setShipmentTrackDetails(
        string $trackNumber,
        string $location,
        string $remark,
        int    $clickPostStatus
    ): array {
        $trackDetailItems = [
            "shipment_id" => "",
            "location" => "",
            "remark" => "",
            "status" => ""
        ];

        try {
            $trackCollection = $this->trackCollection->addFieldToFilter('track_number', $trackNumber);
            if ($trackCollection->getSize() > 0) {
                $trackItem = $trackCollection->getFirstItem();
                $shipment = $trackItem->getShipment();

                /** @var ShipmentTrackUpdates $shipmentTrackUpdates */
                $trackUpdates = $this->shipmentTrackUpdatesFactory->create();
                $trackUpdates->setParentId($shipment->getId())
                    ->setTrackNumber($trackNumber)
                    ->setLocation($location)
                    ->setRemark($remark);

                $statusCollection = $this->statusCollectionFactory->create()
                    ->addFieldToFilter('clickpost_status_code', $clickPostStatus);
                if ($statusCollection->getSize() > 0) {
                    $status = $statusCollection->getFirstItem();
                    $trackUpdates->setStatusId($status->getStatusId());
                }
                $trackUpdates->save();

                $trackDetailItems = [
                    "shipment_id" => $trackUpdates->getParentId(),
                    "location" => $trackUpdates->getLocation(),
                    "remark" => $trackUpdates->getRemark(),
                    "status" => $trackUpdates->getStatusId(),
                ];
            }
        } catch (Exception $exception) {
            $this->apiLogger->error(
                "Tracking Number Not Exists: " . $trackNumber .
                $exception->getMessage() . __METHOD__
            );
        }

        return $this->response->getResponse(
            200,
            'success',
            self::SHIPMENT_API_RESOURCE,
            $trackDetailItems
        );
    }
}
