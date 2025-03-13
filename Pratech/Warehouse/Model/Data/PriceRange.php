<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Your Name <your.email@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Pratech\Warehouse\Api\Data\PriceRangeInterface;

/**
 * Price range implementation
 */
class PriceRange extends AbstractSimpleObject implements PriceRangeInterface
{
    /**
     * @inheritDoc
     */
    public function getFrom(): float
    {
        return (float)$this->_get('from');
    }

    /**
     * @inheritDoc
     */
    public function setFrom(float $from): self
    {
        return $this->setData('from', $from);
    }

    /**
     * @inheritDoc
     */
    public function getTo(): float
    {
        return (float)$this->_get('to');
    }

    /**
     * @inheritDoc
     */
    public function setTo(float $to): self
    {
        return $this->setData('to', $to);
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->_get('label');
    }

    /**
     * @inheritDoc
     */
    public function setLabel($label): self
    {
        // Convert Magento Phrase objects to string
        if ($label instanceof \Magento\Framework\Phrase) {
            $label = (string)$label;
        }

        return $this->setData('label', $label);
    }
}
