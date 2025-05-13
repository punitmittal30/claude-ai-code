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
declare(strict_types=1);

namespace Pratech\ReviewRatings\Model;

use Magento\Framework\Model\AbstractModel;
use Pratech\ReviewRatings\Api\Data\MediaInterface;

class Media extends AbstractModel implements MediaInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Pratech\ReviewRatings\Model\ResourceModel\Media::class);
    }

    /**
     * @inheritDoc
     */
    public function getMediaId()
    {
        return $this->getData(self::MEDIA_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMediaId($mediaId)
    {
        return $this->setData(self::MEDIA_ID, $mediaId);
    }

    /**
     * @inheritDoc
     */
    public function getReviewId()
    {
        return $this->getData(self::REVIEW_ID);
    }

    /**
     * @inheritDoc
     */
    public function setReviewId($reviewId)
    {
        return $this->setData(self::REVIEW_ID, $reviewId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getUrl()
    {
        return $this->getData(self::URL);
    }

    /**
     * @inheritDoc
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
    }
}
