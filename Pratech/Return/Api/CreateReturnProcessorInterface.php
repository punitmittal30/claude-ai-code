<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Return\Api;

use Pratech\Return\Api\Data\ReturnOrderInterface;

/**
 * Interface CreateReturnProcessorInterface
 */
interface CreateReturnProcessorInterface
{
    /**
     * Process.
     *
     * @param  int  $orderId
     * @param  bool $isAdmin
     * @return ReturnOrderInterface|bool
     */
    public function process($orderId, $isAdmin = false);
}
