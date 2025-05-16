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
use Pratech\Warehouse\Model\ResourceModel\Pincode;
use Psr\Log\LoggerInterface;

class PincodeResourceModelPlugin
{
    /**
     * @param CacheServiceInterface $cacheService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        private LoggerInterface       $logger
    )
    {
    }

    /**
     * After save handler
     *
     * @param Pincode $subject
     * @param Pincode $result
     * @param AbstractModel $pincode
     * @return Pincode
     */
    public function afterSave(Pincode $subject, Pincode $result, AbstractModel $pincode)
    {
        try {
            if (method_exists($pincode, 'getPincode') && $pincode->getPincode()) {
                $pincodeValue = $pincode->getPincode();
                $this->logger
                    ->info("Clearing pincode serviceability cache for pincode: {$pincodeValue} from resource plugin");
                $this->cacheService->cleanPincodeCache($pincodeValue);
            }
        } catch (Exception $e) {
            $this->logger->error('Error in PincodeResourceModelPlugin::afterSave: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * After delete handler
     *
     * @param Pincode $subject
     * @param Pincode $result
     * @param AbstractModel $pincode
     * @return Pincode
     */
    public function afterDelete(Pincode $subject, Pincode $result, AbstractModel $pincode)
    {
        try {
            if (method_exists($pincode, 'getPincode') && $pincode->getPincode()) {
                $pincodeValue = $pincode->getPincode();
                $this->logger
                    ->info("Clearing pincode serviceability cache for pincode: {$pincodeValue} from resource plugin");
                $this->cacheService->cleanPincodeCache($pincodeValue);
            }
        } catch (Exception $e) {
            $this->logger->error('Error in PincodeResourceModelPlugin::afterDelete: ' . $e->getMessage());
        }

        return $result;
    }
}
