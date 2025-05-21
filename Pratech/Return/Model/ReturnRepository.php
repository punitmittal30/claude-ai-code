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

use Pratech\Base\Model\Data\Response;
use Pratech\Return\Api\Data\PaymentDetailsInterface;
use Pratech\Return\Api\ReturnRepositoryInterface;
use Pratech\Return\Helper\OrderReturn;

/**
 * Return Repository class to expose api for return.
 */
class ReturnRepository implements ReturnRepositoryInterface
{
    /**
     * Constant for RETURN API RESOURCE
     */
    public const RETURN_API_RESOURCE = 'return';

    /**
     * Return Repository Constructor
     *
     * @param OrderReturn $orderReturnHelper
     * @param Response $response
     */
    public function __construct(
        protected OrderReturn $orderReturnHelper,
        protected Response    $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getReturnReasons(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->getReturnReasons()
        );
    }

    /**
     * @inheritDoc
     */
    public function getReturnStatus(): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->getReturnStatus()
        );
    }

    /**
     * @inheritDoc
     */
    public function createReturnRequest(
        int                      $shipmentId,
        array                    $items = [],
        string                   $comment = null,
        ?PaymentDetailsInterface $paymentDetails = null
    ): array {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->createReturnRequest($shipmentId, $items, $comment, $paymentDetails)
        );
    }

    /**
     * @inheritDoc
     */
    public function getReturnRequest(int $requestId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->getReturnRequest($requestId)
        );
    }

    /**
     * @inheritDoc
     */
    public function updateReturnStatus(string $trackNumber, string $returnStatus): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->updateReturnStatus($trackNumber, $returnStatus)
        );
    }

    /**
     * @inheritDoc
     */
    public function updateRefundStatus(int $requestId, string $refundStatus): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->updateRefundStatus($requestId, $refundStatus)
        );
    }

    /**
     * @inheritDoc
     */
    public function setReturnTrackDetails(
        string $trackNumber,
        string $location,
        string $remark,
        int    $clickPostStatus
    ): array {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->setReturnTrackDetails(
                $trackNumber,
                $location,
                $remark,
                $clickPostStatus
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function cancelReturnRequest(int $requestId): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::RETURN_API_RESOURCE,
            $this->orderReturnHelper->cancelReturnRequest($requestId)
        );
    }
}
