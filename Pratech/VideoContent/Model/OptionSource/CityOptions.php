<?php

namespace Pratech\VideoContent\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Warehouse\Model\ResourceModel\Pincode\CollectionFactory;

class CityOptions implements OptionSourceInterface
{
    /**
     * Constructor
     *
     * @param CollectionFactory $pincodeCollectionFactory
     */
    public function __construct(
        protected CollectionFactory $pincodeCollectionFactory
    ) {
    }

    /**
     * Get unique city options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $collection = $this->pincodeCollectionFactory->create();
        $collection->distinct(true)->addFieldToSelect('city')->addFieldToFilter('city', ['notnull' => true]);

        $options[] = [
            'label' => 'ALL',
            'value' => 'all'
        ];
        foreach ($collection as $item) {
            $city = $item->getCity();
            if ($city) {
                $options[] = [
                    'label' => $city,
                    'value' => $city
                ];
            }
        }

        return $options;
    }
}
