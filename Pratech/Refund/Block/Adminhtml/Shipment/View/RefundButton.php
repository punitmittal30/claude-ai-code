<?php
/**
 * Pratech_Refund
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Refund
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Refund\Block\Adminhtml\Shipment\View;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentInterface;
use Pratech\Refund\Helper\Data as RefundHelper;

class RefundButton extends Container
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context          $context,
        private Registry $registry,
        array            $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Shipment from registry.
     *
     * @return mixed|null
     */
    public function getShipment(): mixed
    {
        return $this->registry->registry('current_shipment');
    }

    /**
     * Is shipment eligible for refund.
     *
     * @param ShipmentInterface $shipment
     * @return bool
     */
    public function isRefundEligibleForShipment(ShipmentInterface $shipment): bool
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
            ->debug('CUSTOM_LOGGING', ['Is Refundable' => $shipment->getRefundedAmount() == 0 || $shipment->getRefundedAmount() == null]);
        return $shipment->getRefundedAmount() == 0 || $shipment->getRefundedAmount() == null;
    }

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();
        if($this->isRefundEligibleForShipment($this->getShipment())){
            $this->addButton(
                'shipment_refund',
                [
                    'label' => __('Refund Shipment'),
                    'class' => 'refund-shipment-button',
                    'onclick' => 'setLocation(\'' . $this->getRefundUrl() . '\')'
                ]
            );
        }
    }

    /**
     * Get Refund URL.
     *
     * @return string
     */
    public function getRefundUrl(): string
    {
        return $this->getUrl(
            'sales/shipment/refund',
            ['shipment_id' => $this->getShipment()->getId()]
        );
    }
}
