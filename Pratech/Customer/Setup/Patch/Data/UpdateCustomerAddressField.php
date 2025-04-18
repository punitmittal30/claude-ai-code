<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Customer\Setup\Patch\Data;

use Exception;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Model\Config as EavConfig;
use Pratech\Base\Logger\Logger;

class UpdateCustomerAddressField implements DataPatchInterface
{
    public const ADDRESS_TYPE = 'address_type';

    public const CUSTOMER_ADDRESS_TYPE = 'customer_address_type';

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param EavConfig $eavConfig
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Logger $apiLogger
     */
    public function __construct(
        private EavSetupFactory          $eavSetupFactory,
        private EavConfig                $eavConfig,
        private ModuleDataSetupInterface $moduleDataSetup,
        private Logger                   $apiLogger
    ) {
    }

    /**
     * Get Dependencies
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Apply Function.
     *
     * @return void
     */
    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->updateAttribute(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::ADDRESS_TYPE,
                'attribute_code',
                self::CUSTOMER_ADDRESS_TYPE
            );
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Get Aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
