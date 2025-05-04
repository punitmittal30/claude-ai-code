<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ProteinCalculator\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Pratech\ProteinCalculator\Model\DietFactory;
use Magento\Framework\Exception\InputException;
use Pratech\Base\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\ProteinCalculator\Model\MultiplierFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Pratech\Catalog\Helper\Product as ProductHelper;

class Data extends AbstractHelper
{
    /**
     * ProteinCalculator Helper Constructor
     *
     * @param Context                    $context
     * @param MultiplierFactory          $multiplierFactory
     * @param DietFactory                $dietFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Logger                     $logger
     * @param ProductHelper              $productHelper
     */
    public function __construct(
        Context $context,
        private MultiplierFactory $multiplierFactory,
        private DietFactory $dietFactory,
        private ProductRepositoryInterface $productRepository,
        private Logger $logger,
        private ProductHelper $productHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Calculate protein needs based on user data.
     *
     * @param int    $age
     * @param int    $weight
     * @param string $height
     * @param string $gender
     * @param string $bodyType
     * @param string $dietType
     * @param string $goal
     * @param string $budget
     *
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function calculateProtein(
        int $age,
        int $weight,
        string $height,
        string $gender,
        string $bodyType,
        string $dietType,
        string $goal,
        string $budget
    ): array {
        // Calculate protein needs
        $proteinNeeded = $this->calculateProteinNeeded($weight, $gender, $bodyType, $goal);

        // Get the diet chart
        $dietChart = $this->getDietChart($dietType, $budget);

        // Calculate total intake
        $totalIntake = $this->calculateTotalIntake($dietChart);

        // Get product details
        $productDetails = $this->getProductDetails($dietType, $budget);

        return [
            'protein_needed_per_day' => $proteinNeeded,
            'diet_chart'             => $dietChart,
            'total_intake'           => $totalIntake,
            'product_data'           => $productDetails,
        ];
    }

    /**
     * Calculate Protein Needed.
     *
     * @param int    $weight
     * @param string $gender
     * @param string $bodyType
     * @param string $goal
     *
     * @return float
     */
    private function calculateProteinNeeded(int $weight, string $gender, string $bodyType, string $goal): float
    {
        $multiplier = $this->getProteinMultiplier($gender, $bodyType, $goal);

        if ($multiplier === null) {
            return $weight * 0.8;
        }

        return $weight * $multiplier;
    }

    /**
     * Get Protein Multiplier from Database.
     *
     * @param string $gender
     * @param string $bodyType
     * @param string $goal
     *
     * @return float|null
     */
    private function getProteinMultiplier(string $gender, string $bodyType, string $goal): ?float
    {
        $collection = $this->multiplierFactory->create()->getCollection();
        $collection->addFieldToFilter('gender', $gender)
                   ->addFieldToFilter('body_type', $bodyType)
                   ->addFieldToFilter('goal', $goal);

        $multiplier = $collection->getFirstItem();

        if ($multiplier && $multiplier->getId()) {
            return (float) $multiplier->getMultiplier();
        }

        return null;
    }

    /**
     * Get Diet Chart based on Diet Type from Database.
     *
     * @param string $dietType
     * @param string $budget
     *
     * @return array
     */
    private function getDietChart(string $dietType, string $budget): array
    {
        $collection = $this->dietFactory->create()->getCollection();
        $collection->addFieldToFilter('diet_type', $dietType)
                   ->addFieldToFilter('budget', $budget);

        $dietChart = [];
        $dietData = $collection->getFirstItem();
        if ($dietData && $dietData->getId()) {
            $dietChart = $dietData->getDiet();
        }

        return $dietChart;
    }

    /**
     * Calculate total intake from diet chart.
     *
     * @param array $dietChart
     *
     * @return float
     */
    private function calculateTotalIntake(array $dietChart): float
    {
        $totalIntake = 0;
        foreach ($dietChart as $item) {
            // Check if the quantity contains a range with "to"
            if (strpos($item['quantity'], 'to') !== false) {
                // Split the quantity range and take the upper value
                list($lower, $upper) = explode(' to ', $item['quantity']);
                $quantity = (float) $upper;
            } else {
                // If not a range, take the quantity as is
                $quantity = (float) $item['quantity'];
            }

            $totalIntake += $quantity;
        }

        return $totalIntake;
    }

    /**
     * Get Product details from Diet model.
     *
     * @param string $dietType
     * @param string $budget
     *
     * @return array
     */
    private function getProductDetails(string $dietType, string $budget): array
    {
        $collection = $this->dietFactory->create()->getCollection();
        $collection->addFieldToFilter('diet_type', $dietType)
                   ->addFieldToFilter('budget', $budget);

        $productIds = $collection->getColumnValues('product_id');

        $productDetails = [];

        foreach ($productIds as $productIdJson) {
            // Attempt to decode the JSON
            $decodedProductIds = json_decode($productIdJson, true);

            if (is_array($decodedProductIds)) {
                // Extract the keys to form the simple array of product IDs
                $productIdsArray = array_keys($decodedProductIds);

                foreach ($productIdsArray as $productId) {
                    if (!empty($productId)) {
                        try {
                            $product = $this->productHelper->formatProductForCarousel($productId);
                            $productDetails[] = $product;
                        } catch (NoSuchEntityException | LocalizedException $e) {
                            // Handle the exception if product not found
                            $this->logger->error("Product not Found | PRODUCT ID:" . $productId .
                                                 $e->getMessage() . __METHOD__);
                        }
                    }
                }
            }
        }

        return $productDetails;
    }
}
