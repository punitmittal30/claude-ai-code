<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model\Resolver\ImageGallery;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Media\ConfigInterface;

class Url implements ResolverInterface
{
    /**
     * @param ConfigInterface $mediaConfig
     */
    public function __construct(
        private ConfigInterface $mediaConfig
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

        /** @var ProductInterface $product */
        $product = $value['model'];

        if (isset($value['image_type'])) {
            $imagePath = $product->getData($value['image_type']);
            return $this->getImageUrl($imagePath);
        } elseif (isset($value['file'])) {
            return $this->getImageUrl($value['file']);
        }
        return [];
    }

    /**
     * Get image URL
     *
     * @param  string $imagePath
     * @return string
     * @throws LocalizedException
     */
    private function getImageUrl(string $imagePath): string
    {
        $baseUrl = $this->mediaConfig->getBaseMediaUrl();

        return $baseUrl . $imagePath;
    }
}
