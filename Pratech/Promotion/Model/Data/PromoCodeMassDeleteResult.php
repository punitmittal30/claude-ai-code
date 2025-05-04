<?php

namespace Pratech\Promotion\Model\Data;

class PromoCodeMassDeleteResult extends \Magento\Framework\Api\AbstractSimpleObject implements
    \Pratech\Promotion\Api\Data\PromoCodeMassDeleteResultInterface
{
    public const FAILED_ITEMS = 'failed_items';
    public const MISSING_ITEMS = 'missing_items';

    /**
     * @inheritdoc
     */
    public function getFailedItems()
    {
        return $this->_get(self::FAILED_ITEMS);
    }

    /**
     * @inheritdoc
     */
    public function setFailedItems(array $items)
    {
        return $this->setData(self::FAILED_ITEMS, $items);
    }

    /**
     * @inheritdoc
     */
    public function getMissingItems()
    {
        return $this->_get(self::MISSING_ITEMS);
    }

    /**
     * @inheritdoc
     */
    public function setMissingItems(array $items)
    {
        return $this->setData(self::MISSING_ITEMS, $items);
    }
}
