<?php
/**
 * Pratech_Quiz
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Quiz
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Quiz\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CustomerLink extends Column
{
    /**
     * Url path
     */
    public const URL_RELATIVE_PATH = 'customer/index/edit';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface     $context,
        UiComponentFactory   $uiComponentFactory,
        private UrlInterface $urlBuilder,
        array                $components = [],
        array                $data = []
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
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (!empty($item[$fieldName])) {
                    $link = $this->urlBuilder->getUrl(static::URL_RELATIVE_PATH, ['id' => $item[$fieldName]]);
                    $item[$fieldName] = "<a target='_blank' href='" . $link . "'>" . $item[$fieldName] . "</a>";
                } else {
                    $item[$fieldName] = "<a href='javascript:void(0)'>" . $item[$fieldName] . "</a>";
                }
            }
        }

        return $dataSource;
    }
}
