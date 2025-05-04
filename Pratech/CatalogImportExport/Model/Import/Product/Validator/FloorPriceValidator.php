<?php
/**
 * Pratech_CatalogImportExport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CatalogImportExport
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CatalogImportExport\Model\Import\Product\Validator;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;
use Magento\Framework\Exception\NoSuchEntityException;

class FloorPriceValidator extends AbstractImportValidator implements RowValidatorInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Is Valid
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        $floorPrice = 0;
        $this->_clearMessages();
        if (!empty($value['price']) || !empty($value['special_price'])) {
            try {
                $floorPrice = $this->productRepository->get($value['sku'])->getCustomAttribute('floor_price')
                    ? $this->productRepository->get($value['sku'])->getCustomAttribute('floor_price')->getValue()
                    : 0;
            } catch (NoSuchEntityException $e) {
                $this->_addMessages(['Invalid sku.']);
            }
            if ($floorPrice != 0 && !empty($value['price'])) {
                if ($value['price'] < $floorPrice) {
                    $this->_addMessages(['Product price cannot be lower than the floor price.']);
                    return false;
                }
            }
            if ($floorPrice != 0 && !empty($value['special_price'])) {
                if ($value['special_price'] < $floorPrice) {
                    $this->_addMessages(['Product special price cannot be lower than the floor price.']);
                    return false;
                }
            }
        }

        return true;
    }
}
