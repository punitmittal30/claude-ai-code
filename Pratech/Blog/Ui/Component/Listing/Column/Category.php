<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Blog\Ui\Component\Listing\Column;

use Exception;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Pratech\Blog\Api\CategoryRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Show Category name on cms listing page.
 */
class Category extends Column
{
    /**
     * Constructor
     *
     * @param ContextInterface            $context
     * @param UiComponentFactory          $uiComponentFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LoggerInterface             $logger
     * @param array                       $components
     * @param array                       $data
     */
    public function __construct(
        ContextInterface                    $context,
        UiComponentFactory                  $uiComponentFactory,
        private CategoryRepositoryInterface $categoryRepository,
        private LoggerInterface             $logger,
        array                               $components = [],
        array                               $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param  array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['category'])) {
                    $category = $item['category'];
                    $item[$this->getData('name')] = $this->getCategoryNameById($category);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get Category Name By Category ID
     *
     * @param  int $categoryId
     * @return string|null
     */
    private function getCategoryNameById(int $categoryId)
    {
        try {
            return $this->categoryRepository->get($categoryId)->getName();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return "";
    }
}
