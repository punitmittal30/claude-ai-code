<?php
/**
 * Pratech_CatalogImportExport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CatalogImportExport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\CatalogImportExport\Model\Import\Product\Validator;

use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;

class PriceValidator extends AbstractImportValidator implements RowValidatorInterface
{
    /**
     * Function to check row is valid or not
     *
     * @param array $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        if (isset($value['price']) && $this->isDecimalValue($value['price'])) {
            $this->_addMessages(
                [
                    'Decimal value not allowed for price.'
                ]
            );
            return false;
        }
        if (isset($value['special_price']) && $this->isDecimalValue($value['special_price'])) {
            $this->_addMessages(
                [
                    'Decimal value not allowed for special price.'
                ]
            );
            return false;
        }

        return true;
    }

    /**
     * Function to check value is decimal or not
     *
     * @param array $value
     * @return boolean
     */
    private function isDecimalValue($value)
    {
        $num = (float) $value;
        if (fmod($num, 1) !== 0.0) {
            return true;
        } else {
            return false;
        }
    }
}
