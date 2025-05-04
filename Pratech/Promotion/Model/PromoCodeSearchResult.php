<?php

declare(strict_types=1);

namespace Pratech\Promotion\Model;

use Magento\Framework\Api\SearchResults;
use Pratech\Promotion\Api\Data\PromoCodeSearchResultInterface;

class PromoCodeSearchResult extends SearchResults implements PromoCodeSearchResultInterface
{
    /**
     * @inheritdoc
     */
    public function setItems(array $items = null)
    {
        return parent::setItems($items);
    }
}
