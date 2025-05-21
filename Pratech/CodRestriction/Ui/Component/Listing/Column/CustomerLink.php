<?php
/**
 * Pratech_CodRestriction
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CodRestriction\Ui\Component\Listing\Column;

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
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * CustomerLink constructor
     *
     * @param UrlInterface       $urlBuilder
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        UrlInterface       $urlBuilder,
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        array              $components = [],
        array              $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
            foreach ($dataSource['data']['items'] as &$item) {
                if (!empty($item['customer_id'])) {
                    $link = $this->urlBuilder->getUrl(static::URL_RELATIVE_PATH, ['id' => $item['customer_id']]);
                    $item['customer'] = "<a target='_blank' href='" . $link . "'>" . $item['customer_id'] . "</a>";
                }
                else {
                    $item['customer'] = "<a href='javascript:void(0)'>" . $item['customer_id'] . "</a>";
                }
            }
        }

        return $dataSource;
    }
}
