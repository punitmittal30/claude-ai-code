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

use Magento\CatalogGraphQl\Model\Resolver\Products;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class CustomSortOrder
{
    /**
     * Custom Sort Order For OOS Product.
     *
     * @param Products $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        Products         $subject,
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
            $args['sort'] = ["item_stock_status" => "DESC", "position" => "ASC"];
        }
        return [$field, $context, $info, $value, $args];
    }
}
