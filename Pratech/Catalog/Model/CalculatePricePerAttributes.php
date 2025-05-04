<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model;

use Exception;
use Magento\Catalog\Model\Product;
use Pratech\Base\Logger\Logger;

/**
 * Calculate Price Per Attributes Model Class
 */
class CalculatePricePerAttributes
{
    /**
     * Update Attributes Constructor
     *
     * @param Logger $apiLogger
     */
    public function __construct(
        private Logger               $apiLogger
    ) {
    }


    /**
     * Calculate
     *
     * @param  Product $product
     * @param  string  $attributeCode
     * @return float
     */
    public function calculate(Product $product, string $attributeCode): float
    {
        switch ($attributeCode) {
            case 'price_per_count':
                return $this->calculatePricePerCount($product);
                break;
            case 'price_per_100_ml':
                return $this->calculatePricePer100ml($product);
                break;
            case 'price_per_100_gram':
                return $this->calculatePricePer100Gram($product);
                break;
            case 'price_per_gram_protein':
                return $this->calculatePricePerGramProtein($product);
                break;
        }
        return 0;
    }

    /**
     * Calculate Price Per Gram Protein
     *
     * @param  Product $product
     * @return float
     */
    public function calculatePricePerGramProtein(Product $product): float
    {
        try {
            $pricePerGramProtein = 0;
            $sellingPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            
            if ($product->getCustomAttribute('number_of_serving_for_price_per_serving')
                && is_numeric($product->getCustomAttribute('number_of_serving_for_price_per_serving')->getValue())
                && $product->getCustomAttribute('number_of_serving_for_price_per_serving')->getValue() > 0
                && $product->getCustomAttribute('protein_per_serving')
                && is_numeric($product->getCustomAttribute('protein_per_serving')->getValue())
                && $product->getCustomAttribute('protein_per_serving')->getValue() > 0
            ) {
                $numberOfServing = $product->getCustomAttribute('number_of_serving_for_price_per_serving')
                    ->getValue();
                $proteinPerServing = $product->getCustomAttribute('protein_per_serving')->getValue();

                $pricePerGramProtein = $sellingPrice / ($numberOfServing * $proteinPerServing);
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
        return $pricePerGramProtein;
    }

    /**
     * Calculate Price Per 100 Gram
     *
     * @param  Product $product
     * @return float
     */
    public function calculatePricePer100Gram(Product $product): float
    {
        try {
            $pricePer100Gram = 0;
            $sellingPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            
            if ($product->getCustomAttribute('number_of_serving_for_price_per_serving')
                && is_numeric($product->getCustomAttribute('number_of_serving_for_price_per_serving')->getValue())
                && $product->getCustomAttribute('number_of_serving_for_price_per_serving')->getValue() > 0
                && $product->getCustomAttribute('protein_per_serving')
                && is_numeric($product->getCustomAttribute('protein_per_serving')->getValue())
                && $product->getCustomAttribute('protein_per_serving')->getValue() > 0
            ) {
                $numberOfServing = $product->getCustomAttribute('number_of_serving_for_price_per_serving')
                    ->getValue();
                $proteinPerServing = $product->getCustomAttribute('protein_per_serving')->getValue();

                $pricePer100Gram = ($sellingPrice / ($numberOfServing * $proteinPerServing)) * 100;
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
        return $pricePer100Gram;
    }

    /**
     * Calculate Price Per Count
     *
     * @param  Product $product
     * @return float
     */
    public function calculatePricePerCount(Product $product): float
    {
        try {
            $pricePerCount = 0;
            $sellingPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            
            if ($product->getCustomAttribute('total_number_of_count')
                && is_numeric($product->getCustomAttribute('total_number_of_count')->getValue())
                && $product->getCustomAttribute('total_number_of_count')->getValue() > 0
            ) {
                $numberOfCount = $product->getCustomAttribute('total_number_of_count')->getValue();

                $pricePerCount = $sellingPrice / $numberOfCount;
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
        return $pricePerCount;
    }

    /**
     * Calculate Price Per 100ml
     *
     * @param  Product $product
     * @return float
     */
    public function calculatePricePer100ml(Product $product): float
    {
        try {
            $pricePer100ml = 0;
            $sellingPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            if ($product->getCustomAttribute('total_volume_in_ml')
                && is_numeric($product->getCustomAttribute('total_volume_in_ml')->getValue())
                && $product->getCustomAttribute('total_volume_in_ml')->getValue() > 0
            ) {
                $totalVolumeInMl = $product->getCustomAttribute('total_volume_in_ml')->getValue();

                $pricePer100ml = ($sellingPrice / $totalVolumeInMl) * 100;
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
        return $pricePer100ml;
    }
}
