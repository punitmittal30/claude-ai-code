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
namespace Pratech\Blog\Ui\Component\Page\Form\Tags;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Blog\Model\TagFactory;

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
     * @param TagFactory $blogTagFactory
     */
    public function __construct(
        protected TagFactory $blogTagFactory
    ) {
    }

    /**
     * Function to return option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $tagCollection = $this->blogTagFactory->create()->getCollection();
        foreach ($tagCollection as $tag) {
            $this->optionsList[] = [
                'value' => $tag->getId(),
                "label" => $tag->getName(),
            ];
        }
        return $this->optionsList;
    }
}
