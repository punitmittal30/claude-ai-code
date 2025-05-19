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

namespace Pratech\Catalog\Model;

use \Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolver for Image Gallery type.
 */
class ImageGalleryTypeResolver implements TypeResolverInterface
{
    /**
     * @inheritdoc
     *
     * @param  array $data
     * @return string
     */
    public function resolveType(array $data) : string
    {
        // resolve type based on the data
        if (isset($data['media_type']) && $data['media_type'] == 'image') {
            return 'ProductImageGallery';
        }
        return '';
    }
}
