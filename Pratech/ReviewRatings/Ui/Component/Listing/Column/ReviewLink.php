<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\ReviewRatings\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ReviewLink extends Column
{
    /**
     * Url path
     */
    public const URL_RELATIVE_PATH = 'review/product/edit';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * ReviewLink constructor
     *
     * @param UrlInterface $urlBuilder
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
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
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
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
