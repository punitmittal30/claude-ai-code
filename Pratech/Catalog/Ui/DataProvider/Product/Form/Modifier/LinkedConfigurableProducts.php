<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class LinkedConfigurableProducts implements ModifierInterface
{

    /**
     * @param LocatorInterface $locator
     */
    public function __construct(
        protected LocatorInterface $locator
    ) {
    }

    /**
     * Hide Linked Configurable Tab
     *
     * @param  array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        $product = $this->locator->getProduct();
        $type = $product->getTypeId();

        if (in_array($type, [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL])) {
            $meta['linked_configurable'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'visible' => false
                        ]
                    ]
                ]
            ];
        }

        return $meta;
    }

    /**
     * @inheriDoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }
}
