<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Textarea;
use Magento\Ui\Component\Form\Field;

class FaqContent extends AbstractModifier
{
    public const PRODUCT_ATTRIBUTE_CODE = 'faq_content';

    /**
     * Dependency Initilization
     *
     * @param LocatorInterface $locator
     * @param AttributeSetCollection $attributeSetCollection
     * @param SerializerInterface $serializer
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        private LocatorInterface            $locator,
        protected AttributeSetCollection    $attributeSetCollection,
        protected SerializerInterface       $serializer,
        protected ArrayManager              $arrayManager
    ) {
    }

    /**
     * Modify Data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $fieldCode = self::PRODUCT_ATTRIBUTE_CODE;

        $model = $this->locator->getProduct();
        $modelId = $model->getId();

        $highlightsData = $model->getFaqContent();

        if ($highlightsData) {
            try {
                $highlightsData = $this->serializer->unserialize($highlightsData, true);
            } catch (\InvalidArgumentException $exception) {
                $highlightsData = [];
            }
            $path = $modelId . '/' . self::DATA_SOURCE_DEFAULT . '/' . $fieldCode;
            $data = $this->arrayManager->set($path, $data, $highlightsData);
        }
        return $data;
    }

    /**
     * Modify Meta
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $highlightsPath = $this->arrayManager->findPath(
            self::PRODUCT_ATTRIBUTE_CODE,
            $meta,
            null,
            'children'
        );

        if ($highlightsPath) {
            $meta = $this->arrayManager->merge(
                $highlightsPath,
                $meta,
                $this->initHighlightFieldStructure($meta, $highlightsPath)
            );
            $meta = $this->arrayManager->set(
                $this->arrayManager->slicePath($highlightsPath, 0, -3)
                    . '/' . self::PRODUCT_ATTRIBUTE_CODE,
                $meta,
                $this->arrayManager->get($highlightsPath, $meta)
            );
            $meta = $this->arrayManager->remove(
                $this->arrayManager->slicePath($highlightsPath, 0, -2),
                $meta
            );
        }

        return $meta;
    }

    /**
     * Get attraction highlights dynamic rows structure
     *
     * @param array $meta
     * @param string $highlightsPath
     * @return array
     */
    protected function initHighlightFieldStructure($meta, $highlightsPath)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'dynamicRows',
                        'label' => __('FAQ'),
                        'renderDefaultRecord' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => '',
                        'dndConfig' => [
                            'enabled' => false,
                        ],
                        'disabled' => false,
                        'sortOrder' =>
                        $this->arrayManager->get($highlightsPath . '/arguments/data/config/sortOrder', $meta),
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Container::NAME,
                                'isTemplate' => true,
                                'is_collection' => true,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => [
                        'question' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => Textarea::NAME,
                                        'componentType' => Field::NAME,
                                        'dataType' => Text::NAME,
                                        'label' => __('Question'),
                                        'dataScope' => 'question',
                                        'require' => '1',
                                    ],
                                ],
                            ],
                        ],
                        'answer' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => Textarea::NAME,
                                        'componentType' => Field::NAME,
                                        'dataType' => Text::NAME,
                                        'label' => __('Answer'),
                                        'dataScope' => 'answer',
                                        'require' => '1',
                                    ],
                                ],
                            ],
                        ],
                        'actionDelete' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => 'actionDelete',
                                        'dataType' => Text::NAME,
                                        'label' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
