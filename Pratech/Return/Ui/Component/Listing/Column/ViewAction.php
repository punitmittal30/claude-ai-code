<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ViewAction extends Column
{
    /**
     * Constructor
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface       $context,
        UiComponentFactory     $uiComponentFactory,
        protected UrlInterface $urlBuilder,
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
                if (isset($item[$this->getData('config/indexField')])) {

                    $urlEntityParamName = $this->getData('config/urlEntityParamName') ?: 'id';
                    $config = (array)$this->getData('config');
                    if ($config && isset($config['buttons'])) {
                        foreach ($config['buttons'] as $actionName => $button) {
                            $label = $button['itemLabel'];
                            $item[$this->getData('name')][$actionName] = [
                                'href' => $this->urlBuilder->getUrl(
                                    $button['urlPath'],
                                    [
                                        $urlEntityParamName => $item[$this->getData('config/indexField')]
                                    ]
                                ),
                                'label' => __($label)
                            ];
                        }
                    }
                }
            }
        }
        return $dataSource;
    }
}
