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
use Pratech\Warehouse\Api\Data\CategoryFilterInterface;

/**
 * Category filter implementation
 */
class CategoryFilter extends AbstractSimpleObject implements CategoryFilterInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return (int)$this->_get('id');
    }

    /**
     * @inheritDoc
     */
    public function setId(int $id): self
    {
        return $this->setData('id', $id);
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

    /**
     * @inheritDoc
     */
    public function getCount(): int
    {
        return (int)$this->_get('count');
    }

    /**
     * @inheritDoc
     */
    public function setCount(int $count): self
    {
        return $this->setData('count', $count);
    }
}
