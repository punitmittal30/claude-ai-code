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

namespace Pratech\Catalog\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirasvit\SearchGraphQl\Model\Resolver\Magento\Catalog\Product;

class CustomSortOrderForSearch
{
    /**
     * Custom Sort Order For OOS Product.
     *
     * @param Product $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        Product         $subject,
        Field            $field,
        ContextInterface $context,
        ResolveInfo      $info,
        array            $value = null,
        array            $args = null
    ): array {
        if (isset($args['sort']) && !empty($args['sort'])) {
            if ($args['sort'] == 'qty_sold') {
                $args['sort'] = array_merge(["item_stock_status" => "DESC"], $args['sort'], ["relevance" => "DESC"]);
            } else {
                $args['sort'] = array_merge(["item_stock_status" => "DESC"], $args['sort']);
            }
        } else {
            unset($args['sort']);
        }
        return [$field, $context, $info, $value, $args];
    }
}
