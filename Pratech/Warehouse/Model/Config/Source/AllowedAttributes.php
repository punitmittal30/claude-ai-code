<?php

namespace Pratech\Warehouse\Model\Config\Source;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\ConfigFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Framework\Exception\LocalizedException;

class AllowedAttributes
{
    /**
     * @var array|string[]
     */
    protected array $exportMainAttributeCodes = [
        'product_websites',
        'category_ids',
        'store',
        'product_online',
        'tax_class_name',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'created_at',
        'updated_at',
        'new_from_date',
        'new_to_date',
        'display_product_options_in',
        'map_price',
        'msrp_price',
        'map_enabled',
        'gift_message_available',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'page_layout',
        'product_options_container',
        'msrp_price',
        'msrp_display_actual_price_type',
        'map_enabled',
        'country_of_manufacture',
        'map_price',
        'display_product_options_in',
        'hide_from_product_page',
        'custom_layout',
        'gallery',
        'has_options',
        'links_exist',
        'links_purchased_separately',
        'links_title',
        'media_gallery',
        'meta_keyword',
        'minimal_price',
        'msrp',
        'news_from_date',
        'news_to_date',
        'old_id',
        'options_container',
        'price_type',
        'price_view',
        'required_options',
        'samples_title',
        'shipment_type',
        'sku_type',
        'tax_class_id',
        'thumbnail',
        'thumbnail_label',
        'tier_price',
        'url_path'
    ];

    /**
     * Allowed Attributes Constructor.
     *
     * @param Collection $collection
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        private Collection    $collection,
        private ConfigFactory $configFactory
    ) {
    }

    /**
     * Get options in 'key-value' format
     *
     * @return array
     */
    public function toArray(): array
    {
        $options = $this->toOptionArray();
        $return = [];

        foreach ($options as $option) {
            $return[$option['value']] = $option['label'];
        }

        return $return;
    }

    /**
     * Options getter
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $entityTypeId = $this->configFactory->create()
            ->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();
        $this->collection->addFieldToFilter(Set::KEY_ENTITY_TYPE_ID, $entityTypeId);
        $attributes = $this->collection->load()->getItems();
        $result = [];
        foreach ($attributes as $attribute) {
            if ((!in_array($attribute['attribute_code'], $this->exportMainAttributeCodes))
                && ($attribute->getIsSystem() == 0)) {
                $result[] = [
                    'value' => $attribute["attribute_code"],
                    'label' => $attribute["frontend_label"] . ' (' . $attribute["attribute_code"] . ')'
                ];
            }
        }
        return $result;
    }
}
