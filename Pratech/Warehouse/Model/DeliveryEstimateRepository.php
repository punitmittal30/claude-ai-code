<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Warehouse\Model;

use Magento\Framework\Exception\LocalizedException;
use Pratech\Warehouse\Api\DeliveryEstimateRepositoryInterface;
use Pratech\Warehouse\Api\PincodeRepositoryInterface;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

class DeliveryEstimateRepository implements DeliveryEstimateRepositoryInterface
{
    /**
     * @param DeliveryDateCalculator $deliveryCalculator
     * @param PincodeRepositoryInterface $pincodeRepository
     */
    public function __construct(
        private DeliveryDateCalculator     $deliveryCalculator,
        private PincodeRepositoryInterface $pincodeRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getEstimate(string $sku, int $pincode): array
    {
        $estimate = $this->deliveryCalculator->getEstimatedDelivery($sku, $pincode);
        $pincodeData = $this->pincodeRepository->getByCode($pincode);

        if (!$pincodeData->getIsServiceable()) {
            throw new LocalizedException(__('Pincode with id "%1" is not serviceable.', $pincode));
        }

        if (!$estimate) {
            return [
                "pincode" => $pincode,
                "city" => $pincodeData->getCity(),
                "state" => $pincodeData->getState(),
                "is_serviceable" => $pincodeData->getIsServiceable()
            ];
        }

        return [
            "pincode" => $pincode,
            "city" => $pincodeData->getCity(),
            "state" => $pincodeData->getState(),
            "is_serviceable" => $pincodeData->getIsServiceable(),
            "warehouse_code" => $estimate['warehouse_code'],
            "delivery_time" => $estimate['delivery_time'],
            "quantity" => $estimate['quantity']
        ];
    }
}
