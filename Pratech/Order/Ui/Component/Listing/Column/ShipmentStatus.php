<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory;

class ShipmentStatus implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Shipment Status Constructor
     *
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        private CollectionFactory $statusCollectionFactory
    ) {
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = array_map(
                fn($status) => [
                    'label' => __($status['status']),
                    'value' => $status['status_id'],  
                ],
                $this->statusCollectionFactory->create()->getData()
            );
        }
        return $this->options;
    }
}
