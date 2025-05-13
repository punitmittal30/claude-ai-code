<?php

namespace Pratech\Cart\Plugin\Model\ResourceModel\Collection;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\ResourceModel\Collection\ExpiredQuotesCollection as CoreExpiredQuotesCollection;

class ExpiredQuotesCollection
{
    /**
     * Update Core Function to consider only guest quotes.
     *
     * @param CoreExpiredQuotesCollection $expiredQuotesCollection
     * @param AbstractCollection $result
     * @return AbstractCollection
     */
    public function afterGetExpiredQuotes(
        CoreExpiredQuotesCollection $expiredQuotesCollection,
        AbstractCollection          $result
    ): AbstractCollection {
        return $result->addFieldToFilter('customer_id', ['null' => true]);
    }
}
