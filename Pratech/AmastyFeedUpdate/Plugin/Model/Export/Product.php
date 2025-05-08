<?php
/**
 * Pratech_AmastyFeedUpdate
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\AmastyFeedUpdate
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\AmastyFeedUpdate\Plugin\Model\Export;

use Exception;
use Amasty\Feed\Model\Export\Product as ProductExport;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as EavCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\CatalogImportExport\Model\Export\Product\Type\Factory as TypeFactory;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Amasty\Feed\Model\Export\RowCustomizer\CompositeFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Amasty\Feed\Model\ResourceModel\Product\CollectionFactory as FeedCollectionFactory;
use Amasty\Feed\Model\Config\Source\NumberFormat;
use Amasty\Feed\Model\InventoryResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Pratech\Base\Logger\Logger;

/**
 * Product Export Model
 */
class Product extends ProductExport
{
    /**
     * Export Product Feed Constructor
     *
     * @param StockRegistry              $stockRegistry
     * @param TimezoneInterface          $localeDate
     * @param Config                     $config
     * @param ResourceConnection         $resource
     * @param StoreManagerInterface      $storeManager
     * @param LoggerInterface            $loggerInterface
     * @param CollectionFactory          $collectionFactory
     * @param ConfigInterface            $exportConfig
     * @param ProductFactory             $productFactory
     * @param EavCollectionFactory       $attrSetColFactory
     * @param CategoryCollectionFactory  $categoryColFactory
     * @param ItemFactory                $itemFactory
     * @param OptionCollectionFactory    $optionColFactory
     * @param AttributeCollectionFactory $attributeColFactory
     * @param TypeFactory                $_typeFactory
     * @param LinkTypeProvider           $linkTypeProvider
     * @param CompositeFactory           $rowCustomizer
     * @param ScopeConfigInterface       $scopeConfig
     * @param FeedCollectionFactory      $collectionAmastyFactory
     * @param NumberFormat               $numberFormat
     * @param InventoryResolver          $inventoryResolver
     * @param ProductRepositoryInterface $productRepository
     * @param Logger                     $logger
     * @param int                        $storeId
     */
    public function __construct(
        StockRegistry $stockRegistry,
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $loggerInterface,
        CollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        ProductFactory $productFactory,
        EavCollectionFactory $attrSetColFactory,
        CategoryCollectionFactory $categoryColFactory,
        ItemFactory $itemFactory,
        OptionCollectionFactory $optionColFactory,
        AttributeCollectionFactory $attributeColFactory,
        TypeFactory $_typeFactory,
        LinkTypeProvider $linkTypeProvider,
        CompositeFactory $rowCustomizer,
        ScopeConfigInterface $scopeConfig,
        FeedCollectionFactory $collectionAmastyFactory,
        NumberFormat $numberFormat,
        InventoryResolver $inventoryResolver,
        private ProductRepositoryInterface $productRepository,
        private Logger $logger,
        $storeId = null
    ) {
        return parent::__construct(
            $stockRegistry,
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $loggerInterface,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $scopeConfig,
            $collectionAmastyFactory,
            $numberFormat,
            $inventoryResolver,
            $storeId
        );
    }
    
    /**
     * @var string
     */
    private $productBaseUrl;

    /**
     * @return string
     */
    public function getProductBaseUrl()
    {
        return $this->productBaseUrl;
    }

    /**
     * @param  string $productBaseUrl
     * @return $this
     */
    public function setProductBaseUrl($productBaseUrl)
    {
        $this->productBaseUrl = $productBaseUrl;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _prepareRowBeforeWrite(&$dataRow)
    {
        try {
            $result = parent::_prepareRowBeforeWrite($dataRow);
            $product = $this->productRepository->get($result['basic|sku']);
            
            $wpProductId = $product->getWpProductId();
            if (isset($wpProductId)) {
                $result['basic|sku'] = $wpProductId;
            }

        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . $exception->getMessage());
        }
       
        return $result;
    }
}
