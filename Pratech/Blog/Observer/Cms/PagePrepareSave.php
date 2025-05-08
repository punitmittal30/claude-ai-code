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
declare(strict_types=1);

namespace Pratech\Blog\Observer\Cms;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;

class PagePrepareSave implements ObserverInterface
{

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ) {
        try {
            $eventData = $observer->getEvent()->getData();

            $page = $eventData['page'];
            $request = $eventData['request'];
            $data = $request->getPostValue();
    
            // to format tag data of blog
            $tag = !empty($data['tag']) ? implode(',', $data['tag']) : '';
            $page->setTag($tag);
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }
}
