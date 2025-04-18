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
namespace Pratech\Blog\Ui\Component\Page\Form\Category;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Blog\Model\CategoryFactory;

/**
 * Tag Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $optionsList = [];

    /**
     * Constructor
     *
     * @param CategoryFactory $blogCategoryFactory
     */
    public function __construct(
        protected CategoryFactory $blogCategoryFactory
    ) {
    }

    /**
     * Function to return option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $categoryCollection = $this->blogCategoryFactory->create()->getCollection();
        foreach ($categoryCollection as $category) {
            $this->optionsList[] = [
                'value' => $category->getId(),
                "label" => $category->getName(),
            ];
        }
        return $this->optionsList;
    }
}
