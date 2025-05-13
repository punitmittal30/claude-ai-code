<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Pratech\Banners\Model\Config\Source\Template as TemplateConfig;

/**
 * Slider Template Class
 */
class Template extends Column
{
    /**
     * Constructor
     *
     * @param TemplateConfig     $templateArray
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        private TemplateConfig $templateArray,
        ContextInterface       $context,
        UiComponentFactory     $uiComponentFactory,
        array                  $components = [],
        array                  $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param  array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['template'])) {
                    $item[$this->getData('name')] = $this->getTypeLabel($item['template']);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Return Type Label based on Type Value.
     *
     * @param  string $identifier
     * @return string
     */
    public function getTypeLabel(string $identifier): string
    {
        $templateOptions = $this->templateArray->toOptionArray();
        foreach ($templateOptions as $template) {
            if ($template['value'] == $identifier) {
                return $template['label'];
            }
        }
        return "";
    }
}
