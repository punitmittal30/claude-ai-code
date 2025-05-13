<?php
/**
 * Pratech_CustomDeliveryCharges
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CustomDeliveryCharges
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CustomDeliveryCharges\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddDeliveryChargesToOrder implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();

        $deliveryCharges = $quote->getDeliveryCharges();
        $baseDeliveryCharges = $quote->getBaseDeliveryCharges();
        if (!$deliveryCharges || !$baseDeliveryCharges) {
            return $this;
        }

        $order = $observer->getOrder();
        $order->setData('delivery_charges', $deliveryCharges);
        $order->setData('base_delivery_charges', $baseDeliveryCharges);

        return $this;
    }
}
