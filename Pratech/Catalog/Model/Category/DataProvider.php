<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model\Category;

/**
 * Data Provider Class to show image data
 */
class DataProvider extends \Magento\Catalog\Model\Category\DataProvider
{
    /**
     * Get Fields Map
     *
     * @return array
     */
    protected function getFieldsMap()
    {
        $fields = parent::getFieldsMap();
        $fields['general'][] = 'show_bubble';
        $fields['general'][] = 'top_brands';
        $fields['content'][] = 'category_thumbnail';
        $fields['content'][] = 'founder_description';
        $fields['shop_by'][] = 'show_in_shop_by';
        $fields['shop_by'][] = 'sequence';
        $fields['shop_by'][] = 'shop_by_image_desktop';
        $fields['shop_by'][] = 'shop_by_image_mobile';
        $fields['search_engine_optimization'][] = 'seo_content';
        return $fields;
    }
}
