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

namespace Pratech\ReviewRatings\Ui\DataProvider;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionManagerInterface;
use Pratech\ReviewRatings\Model\ResourceModel\Media\CollectionFactory;

class ReviewMedia extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Pratech\ReviewRatings\Model\ResourceModel\Media\Collection
     */
    protected $collection;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Http $request
     * @param SessionManagerInterface $session
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        CollectionFactory               $collectionFactory,
        private Http                    $request,
        private SessionManagerInterface $session,
        string                          $name,
        string                          $primaryFieldName,
        string                          $requestFieldName,
        array                           $meta = [],
        array                           $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $reviewId = $this->request->getParam('id', false);
        $this->session->start();
        if ($reviewId) {
            $this->session->setReviewEntityId($reviewId);
        } else {
            $reviewId = $this->session->getReviewEntityId();
        }
        $this->collection = $collectionFactory->create()
            ->addFieldToFilter('review_id', ['eq' => $reviewId]);
    }
}
