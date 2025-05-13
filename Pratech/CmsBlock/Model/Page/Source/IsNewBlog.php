<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model\Page\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Is Top Blog config provider.
 */
class IsNewBlog implements OptionSourceInterface
{
    /**
     * Constructor
     *
     * @param \Magento\Cms\Model\Page $cmsPage
     */
    public function __construct(
        private \Magento\Cms\Model\Page $cmsPage
    ) {
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $availableOptions = $this->cmsPage->getAvailableStatuses();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
