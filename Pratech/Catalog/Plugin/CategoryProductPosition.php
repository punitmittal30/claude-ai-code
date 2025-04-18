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

namespace Pratech\Catalog\Plugin;

use Magento\Catalog\Model\Category;

class CategoryProductPosition
{
    /**
     * Before Save Plugin to update product position in category.
     *
     * @param Category $subject
     * @return array
     */
    public function beforeSave(
        Category $subject
    ): array {
        $productPositions = $subject->getPostedProducts();
        if (!empty($productPositions)) {
            foreach ($productPositions as $productId => $position) {
                if ($position == "") {
                    $productPositions[$productId] = 2000;
                }
            }
            $subject->setPostedProducts($productPositions);
        }
        return [];
    }
}
