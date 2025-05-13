<?php

namespace Pratech\CatalogImportExport\Plugin\Model\Export;

use Exception;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogImportExport\Model\Export\Product\Type\Factory;
use Magento\CatalogImportExport\Model\Export\ProductFilterInterface;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Catalog\Model\ResourceModel\LinkedProduct as LinkedProductResource;
use Psr\Log\LoggerInterface;

class Product extends \Magento\CatalogImportExport\Model\Export\Product
{
    /**
     * Image labels array ---> No change in this class variable
     *
     * @var array
     */
    private $imageLabelAttributes = [
        'base_image_label',
        'small_image_label',
        'thumbnail_image_label',
        'swatch_image_label',
    ];

    /**
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param ConfigInterface $exportConfig
     * @param ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param CollectionFactory $attributeColFactory
     * @param Factory $_typeFactory
     * @param LinkTypeProvider $linkTypeProvider
     * @param RowCustomizerInterface $rowCustomizer
     * @param ScopeConfigInterface $scopeConfig
     * @param LinkedProductResource $linkedProductResource
     * @param ProductResource $productResource
     * @param array $dateAttrCodes
     * @param ProductFilterInterface|null $filter
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        TimezoneInterface                                                       $localeDate,
        Config                                                                  $config,
        ResourceConnection                                                      $resource,
        StoreManagerInterface                                                   $storeManager,
        LoggerInterface                                                         $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory          $collectionFactory,
        ConfigInterface                                                         $exportConfig,
        ProductFactory                                                          $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory         $categoryColFactory,
        ItemFactory                                                             $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory   $optionColFactory,
        CollectionFactory                                                       $attributeColFactory,
        Factory                                                                 $_typeFactory,
        LinkTypeProvider                                                        $linkTypeProvider,
        RowCustomizerInterface                                                  $rowCustomizer,
        private ScopeConfigInterface                                            $scopeConfig,
        private LinkedProductResource                                           $linkedProductResource,
        private ProductResource                                                 $productResource,
        array                                                                   $dateAttrCodes = [],
        ?ProductFilterInterface                                                 $filter = null
    ) {
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
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
            $dateAttrCodes,
            $filter
        );
    }

    /**
     * Get export data for collection
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function getExportData(): array
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds = array_keys($rawData);
            $stockItemRows = $this->prepareCatalogInventory($productIds);

            $linkedSkus = $this->getLinkedProductSkus($productIds);

            $this->rowCustomizer->prepareData(
                $this->_prepareEntityCollection($this->_entityCollectionFactory->create()),
                $productIds
            );

            $this->setHeaderColumns($multirawData['customOptionsData'], $stockItemRows);

            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if ($storeId == Store::DEFAULT_STORE_ID && isset($stockItemRows[$productId])) {
                        // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                        $dataRow = array_merge(["entity_id" => $productId], $dataRow, $stockItemRows[$productId]);

                        if (isset($linkedSkus[$productId])) {
                            $dataRow['linked_products'] = implode(',', $linkedSkus[$productId]);
                        } else {
                            $dataRow['linked_products'] = '';
                        }
                    }
                    $this->updateGalleryImageData($dataRow, $rawData);
                    $this->appendMultirowData($dataRow, $multirawData);
                    if ($dataRow) {
                        $exportData[] = $dataRow;
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }
        return $exportData;
    }

    /**
     * Get Linked Product Skus
     *
     * @param array $productIds
     * @return array
     */
    private function getLinkedProductSkus(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $linkedProductIds = [];
        foreach ($productIds as $productId) {
            $linkedProductIds[$productId] = $this->linkedProductResource->getLinkedProducts($productId);
        }

        $allLinkedIds = array_unique(array_merge(...array_values($linkedProductIds)));

        if (empty($allLinkedIds)) {
            return [];
        }

        $productSkuList = $this->productResource->getProductsSku($allLinkedIds);
        $productSkuMap = array_column($productSkuList, 'sku', 'entity_id');

        $linkedSkus = [];
        foreach ($linkedProductIds as $productId => $linkedIdsArray) {
            $skus = [];
            foreach ($linkedIdsArray as $linkedId) {
                if (isset($productSkuMap[$linkedId])) {
                    $skus[] = $productSkuMap[$linkedId];
                }
            }
            $linkedSkus[$productId] = $skus;
        }
        return $linkedSkus;
    }

    /** 
     * Set header columns
     *
     * @param array $customOptionsData
     * @param array $stockItemRows
     */
    protected function setHeaderColumns($customOptionsData, $stockItemRows)
    {
        $isModuleEnabled = $this->scopeConfig->getValue(
            'product_export/configuration/enable',
            ScopeInterface::SCOPE_STORE
        );

        $merge = [];

        if ($isModuleEnabled) {
            $allowedAttributes = $this->scopeConfig->getValue(
                'product_export/configuration/allowed_attributes',
                ScopeInterface::SCOPE_STORE
            );
            $merge = explode(',', $allowedAttributes);
        }

        if (!$this->_headerColumns) {
            $customOptCols = [
                'custom_options',
            ];
            $this->_headerColumns = array_merge(
                [
                    'entity_id',
                    self::COL_SKU,
                    self::COL_STORE,
                    self::COL_ATTR_SET,
                    self::COL_TYPE,
                    self::COL_CATEGORY,
                    self::COL_PRODUCT_WEBSITES,
                ],
                $this->_getExportMainAttrCodes(),
                $merge,
                [self::COL_ADDITIONAL_ATTRIBUTES],
                reset($stockItemRows) ? array_keys(end($stockItemRows)) : [],
                [],
                [
                    'related_skus',
                    'related_position',
                    'crosssell_skus',
                    'crosssell_position',
                    'upsell_skus',
                    'upsell_position',
                    'linked_products'
                ],
                ['additional_images', 'additional_image_labels', 'hide_from_product_page']
            );

            if ($customOptionsData) {
                $this->_headerColumns = array_merge($this->_headerColumns, $customOptCols);
            }
        }
    }

    /**
     * Add image column if image label exists for all scope ---> No change in this method
     *
     * @param array $dataRow
     * @param array $rawData
     * @return void
     */
    private function updateGalleryImageData(&$dataRow, $rawData): void
    {
        $storeId = $dataRow['store_id'];
        $productId = $dataRow['product_id'];
        foreach ($this->imageLabelAttributes as $imageLabelCode) {
            $imageAttributeCode = str_replace('_label', '', $imageLabelCode);
            if ($storeId != Store::DEFAULT_STORE_ID
                && isset($dataRow[$imageLabelCode])
                && $dataRow[$imageLabelCode]
                && (!isset($dataRow[$imageAttributeCode]) || !$dataRow[$imageAttributeCode])
            ) {
                $dataRow[$imageAttributeCode] = $rawData[$productId][Store::DEFAULT_STORE_ID][$imageAttributeCode];
            }
        }
    }

    /**
     * Append multi row data ---> No change in this method
     *
     * @param array $dataRow
     * @param array $multiRawData
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function appendMultirowData(&$dataRow, $multiRawData)
    {
        $productId = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId = $dataRow['store_id'];
        $sku = $dataRow[self::COL_SKU];
        $type = $dataRow[self::COL_TYPE];
        $attributeSet = $dataRow[self::COL_ATTR_SET];

        unset($dataRow['product_id']);
        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        unset($dataRow[self::COL_STORE]);
        unset($dataRow[self::COL_ATTR_SET]);
        unset($dataRow[self::COL_TYPE]);

        if (Store::DEFAULT_STORE_ID == $storeId) {
            $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $productId);
            if (!empty($multiRawData['rowWebsites'][$productId])) {
                $websiteCodes = [];
                foreach ($multiRawData['rowWebsites'][$productId] as $productWebsite) {
                    $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
                }
                $dataRow[self::COL_PRODUCT_WEBSITES] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $websiteCodes);
                $multiRawData['rowWebsites'][$productId] = [];
            }
            if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
                $additionalImages = [];
                $additionalImageLabels = [];
                $additionalImageIsDisabled = [];
                foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                    if ((int)$mediaItem['_media_store_id'] === Store::DEFAULT_STORE_ID) {
                        $additionalImages[] = $mediaItem['_media_image'];
                        $additionalImageLabels[] = $mediaItem['_media_label'];

                        if ($mediaItem['_media_is_disabled'] == true) {
                            $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                        }
                    }
                }
                $dataRow['additional_images'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImages);
                $dataRow['additional_image_labels'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageLabels);
                $dataRow['hide_from_product_page'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageIsDisabled);
                $multiRawData['mediaGalery'][$productLinkId] = [];
            }
            foreach ($this->_linkTypeProvider->getLinkTypes() as $linkTypeName => $linkId) {
                if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                    $colPrefix = $linkTypeName . '_';

                    $associations = [];
                    foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                        if ($linkData['default_qty'] !== null) {
                            $skuItem = $linkData['sku'] . ImportProduct::PAIR_NAME_VALUE_SEPARATOR .
                                $linkData['default_qty'];
                        } else {
                            $skuItem = $linkData['sku'];
                        }
                        $associations[$skuItem] = $linkData['position'];
                    }
                    $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                    asort($associations);
                    $dataRow[$colPrefix . 'skus'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_keys($associations));
                    $dataRow[$colPrefix . 'position'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_values($associations));
                }
            }
            $dataRow = $this->rowCustomizer->addData($dataRow, $productId);
        } else {
            $additionalImageIsDisabled = [];
            if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
                foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                    if ((int)$mediaItem['_media_store_id'] === $storeId) {
                        if ($mediaItem['_media_is_disabled'] == true) {
                            $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                        }
                    }
                }
            }
            if ($additionalImageIsDisabled) {
                $dataRow['hide_from_product_page'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageIsDisabled);
            }
        }

        if (!empty($this->collectedMultiselectsData[$storeId][$productId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productId][$attrKey])) {
                    $dataRow[$attrKey] = implode(
                        Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                        $this->collectedMultiselectsData[$storeId][$productId][$attrKey]
                    );
                }
            }
        }

        if (!empty($multiRawData['customOptionsData'][$productLinkId][$storeId])) {
            $shouldBeMerged = true;
            $customOptionsRows = $multiRawData['customOptionsData'][$productLinkId][$storeId];

            if ($storeId != Store::DEFAULT_STORE_ID
                && !empty($multiRawData['customOptionsData'][$productLinkId][Store::DEFAULT_STORE_ID])
            ) {
                $defaultCustomOptions = $multiRawData['customOptionsData'][$productLinkId][Store::DEFAULT_STORE_ID];
                if (!array_diff($defaultCustomOptions, $customOptionsRows)) {
                    $shouldBeMerged = false;
                }
            }

            if ($shouldBeMerged) {
                $multiRawData['customOptionsData'][$productLinkId][$storeId] = [];
                $customOptions = implode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $customOptionsRows);
                $dataRow = array_merge($dataRow, ['custom_options' => $customOptions]);
            }
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
        }
        $dataRow[self::COL_SKU] = $sku;
        $dataRow[self::COL_ATTR_SET] = $attributeSet;
        $dataRow[self::COL_TYPE] = $type;

        return $dataRow;
    }
}
