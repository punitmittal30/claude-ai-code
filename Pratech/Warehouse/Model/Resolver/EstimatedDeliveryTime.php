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

namespace Pratech\Warehouse\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

class EstimatedDeliveryTime implements ResolverInterface
{
    /**
     * @param DeliveryDateCalculator $deliveryDateCalculator
     */
    public function __construct(
        private DeliveryDateCalculator $deliveryDateCalculator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!isset($value['sku'])) {
            throw new GraphQlInputException(__('SKU should be specified'));
        }

        if (empty($args['pincode'])) {
            throw new GraphQlInputException(__('Pincode must be specified'));
        }

//        $context->getExtensionAttributes()->setPincode($args['pincode']);
        return $this->deliveryDateCalculator->getEstimatedDelivery($value['sku'], $args['pincode']);
    }
}
