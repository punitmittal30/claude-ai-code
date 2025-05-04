<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Slider Location Class
 */
class Type extends Column
{
    /**
     * Constructor
     *
     * @param \Pratech\Banners\Model\Config\Source\Type $typeArray
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        private \Pratech\Banners\Model\Config\Source\Type $typeArray,
        ContextInterface                                  $context,
        UiComponentFactory                                $uiComponentFactory,
        array                                             $components = [],
        array                                             $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['type'])) {
                    $type = str_replace("_", " ", $item['type']);
                    $item[$this->getData('name')] = $this->getTypeLabel($type);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Return Type Label based on Type Value.
     *
     * @param string $identifier
     * @return string
     */
    public function getTypeLabel(string $identifier): string
    {
        $typeOptions = $this->typeArray->toOptionArray();
        foreach ($typeOptions as $typeOption) {
            if ($typeOption['value'] != '') {
                foreach ($typeOption['value'] as $type) {
                    if ($type['value'] == $identifier) {
                        return $type['label'];
                    }
                }
            }
        }
        return "";
    }
}
