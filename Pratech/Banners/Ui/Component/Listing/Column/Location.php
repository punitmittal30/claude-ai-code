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

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Pratech\Base\Logger\Logger;

/**
 * Slider Location Class
 */
class Location extends Column
{
    /**
     * Constructor
     *
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Logger $apiLogger
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private Logger                      $apiLogger,
        ContextInterface                    $context,
        UiComponentFactory                  $uiComponentFactory,
        array                               $components = [],
        array                               $data = []
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
                if (isset($item['location'])) {
                    $location = ucwords(str_replace("_", " ", $item['location']));
                    try {
                        $location = $this->categoryRepository->get($location)->getName();
                    } catch (NoSuchEntityException $e) {
                        $this->apiLogger->error($e->getMessage() . __METHOD__);
                    }
                    $item[$this->getData('name')] = $location;
                }
            }
        }

        return $dataSource;
    }
}
