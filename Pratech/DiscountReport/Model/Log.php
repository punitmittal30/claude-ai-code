<?php
/**
 * Pratech_DiscountReport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\DiscountReport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\DiscountReport\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\DiscountReport\Api\Data\LogInterface;

class Log extends AbstractModel implements LogInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ResourceModel\Log::class);
    }

    /**
     * @inheritDoc
     */
    public function getLogId()
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLogId($logId)
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getItemSku()
    {
        return $this->getData(self::ITEM_SKU);
    }

    /**
     * @inheritDoc
     */
    public function setItemSku($itemSku)
    {
        return $this->setData(self::ITEM_SKU, $itemSku);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountData()
    {
        return $this->getData(self::DISCOUNT_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountData($discountData)
    {
        return $this->setData(self::DISCOUNT_DATA, $discountData);
    }
}
