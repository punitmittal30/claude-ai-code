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
use Pratech\ReviewRatings\Api\Data\KeywordsInterface;

class Keywords extends AbstractModel implements KeywordsInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Pratech\ReviewRatings\Model\ResourceModel\Keywords::class);
    }

    /**
     * @inheritDoc
     */
    public function getKeywordsId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setKeywordsId($keywordsId)
    {
        return $this->setData(self::ENTITY_ID, $keywordsId);
    }

    /**
     * @inheritDoc
     */
    public function getMappingValue()
    {
        return $this->getData(self::MAPPING_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setMappingValue($mappingValue)
    {
        return $this->setData(self::MAPPING_VALUE, $mappingValue);
    }

    /**
     * @inheritDoc
     */
    public function getRatingOne()
    {
        return $this->getData(self::RATING_ONE);
    }

    /**
     * @inheritDoc
     */
    public function setRatingOne($ratingOne)
    {
        return $this->setData(self::RATING_ONE, $ratingOne);
    }

    /**
     * @inheritDoc
     */
    public function getRatingTwo()
    {
        return $this->getData(self::RATING_TWO);
    }

    /**
     * @inheritDoc
     */
    public function setRatingTwo($ratingTwo)
    {
        return $this->setData(self::RATING_TWO, $ratingTwo);
    }

    /**
     * @inheritDoc
     */
    public function getRatingThree()
    {
        return $this->getData(self::RATING_THREE);
    }

    /**
     * @inheritDoc
     */
    public function setRatingThree($ratingThree)
    {
        return $this->setData(self::RATING_THREE, $ratingThree);
    }

    /**
     * @inheritDoc
     */
    public function getRatingFour()
    {
        return $this->getData(self::RATING_FOUR);
    }

    /**
     * @inheritDoc
     */
    public function setRatingFour($ratingFour)
    {
        return $this->setData(self::RATING_FOUR, $ratingFour);
    }

    /**
     * @inheritDoc
     */
    public function getRatingFive()
    {
        return $this->getData(self::RATING_FIVE);
    }

    /**
     * @inheritDoc
     */
    public function setRatingFive($ratingFive)
    {
        return $this->setData(self::RATING_FIVE, $ratingFive);
    }
}
