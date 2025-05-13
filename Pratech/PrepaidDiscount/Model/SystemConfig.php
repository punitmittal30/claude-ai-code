<?php
/**
 * Pratech_PrepaidDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\PrepaidDiscount
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\PrepaidDiscount\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Model\Data\Response;
use Pratech\PrepaidDiscount\Api\SystemConfigInterface;

/**
 * System Config model class to return system config values.
 */
class SystemConfig implements SystemConfigInterface
{
    /**
     * Constant for success code.
     */
    public const SUCCESS_CODE = 200;

    /**
     * Constant for system config api resource.
     */
    public const SYSTEM_CONFIG_API_RESOURCE = 'system_config';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Response $response
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Response             $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getPrepaidDiscountInfo(): array
    {
        $prepaidDiscountSlab = [];
        $types = $this->scopeConfig->getValue('prepaid_discount/general/ranges', ScopeInterface::SCOPE_STORE);
        if ($types) {
            $items = json_decode($types, true);
            foreach ($items as $item) {
                $prepaidDiscountSlab[] = [
                    "from_price" => $item["from_price"],
                    "to_price" => $item["to_price"],
                    "discount_type" => $item["discount_type"],
                    "discount" => $item['discount']
                ];
            }
        }
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::SYSTEM_CONFIG_API_RESOURCE,
            $prepaidDiscountSlab
        );
    }
}
