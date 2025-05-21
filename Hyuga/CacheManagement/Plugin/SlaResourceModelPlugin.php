<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Plugin;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Magento\Framework\Model\AbstractModel;
use Pratech\Warehouse\Model\ResourceModel\WarehouseSla;
use Psr\Log\LoggerInterface;

class SlaResourceModelPlugin
{
    /**
     * @param CacheServiceInterface $cacheService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private LoggerInterface       $logger
    ) {
    }

    /**
     * After save handler
     *
     * @param WarehouseSla $subject
     * @param WarehouseSla $result
     * @param AbstractModel $sla
     * @return WarehouseSla
     */
    public function afterSave(WarehouseSla $subject, WarehouseSla $result, AbstractModel $sla)
    {
        try {
            if (method_exists($sla, 'getCustomerPincode') && $sla->getCustomerPincode()) {
                $pincode = $sla->getCustomerPincode();
                $this->logger
                    ->info("Clearing pincode serviceability cache for pincode: {$pincode} from SLA resource plugin");
                $this->cacheService->cleanPincodeCache($pincode);
            }
        } catch (Exception $e) {
            $this->logger->error('Error in SlaResourceModelPlugin::afterSave: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * After delete handler
     *
     * @param WarehouseSla $subject
     * @param WarehouseSla $result
     * @param AbstractModel $sla
     * @return WarehouseSla
     */
    public function afterDelete(WarehouseSla $subject, WarehouseSla $result, AbstractModel $sla)
    {
        try {
            if (method_exists($sla, 'getCustomerPincode') && $sla->getCustomerPincode()) {
                $pincode = $sla->getCustomerPincode();
                $this->logger
                    ->info("Clearing pincode serviceability cache for pincode: {$pincode} from SLA resource plugin");
                $this->cacheService->cleanPincodeCache($pincode);
            }
        } catch (Exception $e) {
            $this->logger->error('Error in SlaResourceModelPlugin::afterDelete: ' . $e->getMessage());
        }

        return $result;
    }
}
