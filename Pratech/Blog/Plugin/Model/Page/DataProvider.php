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

namespace Pratech\Blog\Plugin\Model\Page;

use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Logger\Logger;

/**
 * Data Provider Class to manage blog data.
 */
class DataProvider
{
    /**
     * @param Logger $apiLogger
     */
    public function __construct(
        private Logger $apiLogger
    ) {
    }

    /**
     * After Get Data.
     *
     * @param  \Magento\Cms\Model\Page\DataProvider $subject
     * @param  array                                $result
     * @return array
     */
    public function afterGetData(
        \Magento\Cms\Model\Page\DataProvider $subject,
        array                                $result
    ): array {
        try {
            foreach ($result as $key => $page) {
                if (isset($page['tag'])) {
                    $tagString = $page['tag'] ?? '';
                    $result[$key]['tag'] = explode(',', $tagString);
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->apiLogger->critical($e->getMessage() . __METHOD__);
        }
        return $result;
    }
}
