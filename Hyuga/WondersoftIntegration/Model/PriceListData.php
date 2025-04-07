<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;

class PriceListData extends DataObject
{
    /**
     * Prepare multiple price list entries for a product
     *
     * @param ProductInterface $product
     * @param array $priceData [priceListId => [name => 'Name', price => 123.45, customerGroup => 'code']]
     * @param string $storeCode
     * @return array
     */
    public function prepareMultiplePriceListData(
        ProductInterface $product,
        array            $priceData,
        string           $storeCode = 'default'
    ): array
    {
        $result = [];

        foreach ($priceData as $priceListId => $data) {
            $result[] = $this->preparePriceListData(
                $priceListId,
                $data['name'] ?? 'Default Price List',
                $product,
                $data['price'] ?? $product->getPrice(),
                $data['customerGroup'] ?? 'DEFAULT',
                $storeCode
            );
        }

        return $result;
    }

    /**
     * Prepare price list data for pushing to eShopaid
     *
     * @param string $priceListId
     * @param string $priceListName
     * @param ProductInterface $product
     * @param float $price
     * @param string $customerGroupCode
     * @param string $storeCode
     * @return array
     */
    public function preparePriceListData(
        string           $priceListId,
        string           $priceListName,
        ProductInterface $product,
        float            $price,
        string           $customerGroupCode = 'DEFAULT',
        string           $storeCode = 'default'
    ): array
    {
        return [
            'PriceListID' => $priceListId,
            'PriceListName' => $priceListName,
            'ProductCode' => $product->getSku(),
            'Rate' => number_format($price, 2, '.', ''),
            'MRPLessPercentage' => '',
            'IsActive' => 1,
            'AlternateStoreCode' => $storeCode,
            'CustomerGroupCode' => $customerGroupCode
        ];
    }
}
