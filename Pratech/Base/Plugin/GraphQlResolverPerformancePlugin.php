<?php
/**
 * Pratech_Base
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Base
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Base\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Pratech\Base\Logger\GraphQlLogger;

/**
 * Plugin to monitor GraphQL resolver performance
 */
class GraphQlResolverPerformancePlugin
{
    /**
     * @var array
     */
    private $timings = [];

    /**
     * @param GraphQlLogger $graphQlLogger
     */
    public function __construct(
        private GraphQlLogger $graphQlLogger,
    ) {
    }

    /**
     * Before plugin for resolver execution
     *
     * @param ResolverInterface $subject
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        ResolverInterface $subject,
        Field             $field,
        $context,
        ResolveInfo       $info,
        array             $value = null,
        array             $args = null
    ) {
        $resolverClass = get_class($subject);
        $path = implode('.', $info->path ?? []);
        $fieldName = $field->getName();

        // Get product ID if available
        $productId = 'N/A';
        if (is_array($value) && isset($value['model']) && method_exists($value['model'], 'getId')) {
            $productId = $value['model']->getId();
        }

        $key = "$path:$fieldName:$resolverClass";
        $this->timings[$key] = [
            'start' => microtime(true),
            'path' => $path,
            'field' => $fieldName,
            'resolver' => $resolverClass,
            'product_id' => $productId,
        ];

        return [$field, $context, $info, $value, $args];
    }

    /**
     * After plugin for resolver execution
     *
     * @param ResolverInterface $subject
     * @param mixed $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function afterResolve(
        ResolverInterface $subject,
        $result,
        Field             $field,
        $context,
        ResolveInfo       $info,
        array             $value = null,
        array             $args = null
    ) {
        $resolverClass = get_class($subject);
        $path = implode('.', $info->path ?? []);
        $fieldName = $field->getName();

        $key = "$path:$fieldName:$resolverClass";

        if (isset($this->timings[$key])) {
            $timing = $this->timings[$key];
            $endTime = microtime(true);
            $executionTime = ($endTime - $timing['start']) * 1000; // Convert to milliseconds

            // Log slow resolvers (taking more than 50ms)
            if ($executionTime > 50) {
                $this->graphQlLogger->debug(sprintf(
                    'SLOW RESOLVER: %s.%s took %.2f ms (Resolver: %s, Product ID: %s)',
                    $path,
                    $fieldName,
                    $executionTime,
                    $resolverClass,
                    $timing['product_id']
                ));
            }

            unset($this->timings[$key]);
        }

        return $result;
    }
}
