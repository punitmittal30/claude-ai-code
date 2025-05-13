<?php
/**
 * Pratech_OfflinePaymentMethods
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\OfflinePaymentMethods
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\OfflinePaymentMethods\Model\Payment;

class NetBanking extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var string
     */
    protected $_code = "netbanking";

    /**
     * @var bool
     */
    protected $_isOffline = true;
}
