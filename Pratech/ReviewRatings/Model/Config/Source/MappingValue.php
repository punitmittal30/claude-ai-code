<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Catalog\Helper\Eav;

/**
 * MappingValue Option Provider Class
 */
class MappingValue implements OptionSourceInterface
{

    /**
     * Constructor
     *
     * @param Eav $eavHelper
     */
    public function __construct(
        private Eav $eavHelper
    ) {
    }

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getOptions() as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        $this->getAttributeOptions('categorization');

        return $options;
    }

    /**
     * Get Options
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = [];
        $mappingOptions = $this->getAttributeOptions('categorization');
        foreach ($mappingOptions as $mappingOption) {
            if ($mappingOption['value']) {
                $options[$mappingOption['label']] = $mappingOption['label'];
            }
        }

        return $options;
    }

    /**
     * Get Attribute Options
     *
     * @param string $attributeCode
     * @return array
     */
    public function getAttributeOptions(string $attributeCode): array
    {
        return $this->eavHelper->getAttributeOptions($attributeCode);
    }
}
