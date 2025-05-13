<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\CmsBlock\Model\ResourceModel\Author\CollectionFactory;

/**
 * Author Option Provider Class
 */
class Author implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        protected CollectionFactory $collectionFactory
    ) {
    }

    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options[] = [
            'value' => null,
            'label' => ' '
        ];
        $authorCollection = $this->collectionFactory->create();

        if ($authorCollection->getSize()) {
            foreach ($authorCollection as $author) {
                $options[] = [
                    'value' => $author->getAuthorId(),
                    'label' => $author->getAuthorName()
                ];
            }
        }
        return $options;
    }
}
