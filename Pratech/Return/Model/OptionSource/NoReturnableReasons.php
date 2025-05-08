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

namespace Pratech\Return\Model\OptionSource;

class NoReturnableReasons
{
    public const ALREADY_RETURNED = 0;
    public const EXPIRED_PERIOD = 1;
    public const REFUNDED = 2;
    public const ITEM_WASNT_SHIPPED = 3;
    public const ITEM_WAS_ON_SALE = 4;
}
