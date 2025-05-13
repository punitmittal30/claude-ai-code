<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Model\PromoCode;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Pratech\Promotion\Helper\Code;
use Pratech\Promotion\Model\PromoCodeFactory;

class Massgenerator extends \Magento\Framework\Model\AbstractModel implements
    \Pratech\Promotion\Model\PromoCode\CodegeneratorInterface
{
    /**
     * Maximum probability of guessing the promo on the first attempt
     */
    public const MAX_PROBABILITY_OF_GUESSING = 0.25;

    /**
     * Number of attempts to generate
     */
    public const MAX_GENERATE_ATTEMPTS = 10;

    /**
     * Count of generated codes
     * @var int
     */
    protected $generatedCount = 0;

    /**
     * @var array
     */
    protected $generatedCodes = [];

    /**
     * Promo Code Helper
     *
     * @var \Pratech\Promotion\Helper\Code
     */
    protected $codeHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Pratech\Promotion\Model\PromoCodeFactory
     */
    protected $promoCodeFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Code $codeHelper
     * @param PromoCodeFactory $promoCodeFactory
     * @param DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Pratech\Promotion\Helper\Code $codeHelper,
        \Pratech\Promotion\Model\PromoCodeFactory $promoCodeFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->codeHelper = $codeHelper;
        $this->date = $date;
        $this->promoCodeFactory = $promoCodeFactory;
        $this->dateTime = $dateTime;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Pratech\Promotion\Model\ResourceModel\PromoCode::class);
    }

    /**
     * Generate promo code
     *
     * @return string
     * @throws LocalizedException
     */
    public function generateCode()
    {
        $format = $this->getFormat();
        if (empty($format)) {
            $format = \Pratech\Promotion\Helper\Code::CODE_FORMAT_ALPHANUMERIC;
        }

        $charset = $this->codeHelper->getCharset($format);

        $code = '';
        $charsetSize = count($charset);
        $length = max(1, (int)$this->getLength());
        for ($i = 0; $i < $length; ++$i) {
            $char = $charset[\Magento\Framework\Math\Random::getRandomNumber(0, $charsetSize - 1)];
            $code .= $char;
        }

        return $code;
    }

    /**
     * Retrieve delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        if ($this->hasData('delimiter')) {
            return $this->getData('delimiter');
        } else {
            return $this->codeHelper->getCodeSeparator();
        }
    }

    /**
     * Generate promos Pool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    public function generatePool()
    {
        $this->generatedCount = 0;
        $this->generatedCodes = [];
        $size = $this->getQty();
        $maxAttempts = $this->getMaxAttempts() ? $this->getMaxAttempts() : self::MAX_GENERATE_ATTEMPTS;
        $this->increaseLength();
        /** @var $promoCode \Pratech\Promotion\Model\PromoCode */
        $promoCode = $this->promoCodeFactory->create();
        $nowTimestamp = $this->dateTime->formatDate($this->date->gmtTimestamp());

        for ($i = 0; $i < $size; $i++) {
            $attempt = 0;
            do {
                if ($attempt >= $maxAttempts) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('We cannot create the requested Qty. Please check your settings and try again.')
                    );
                }
                $code = $this->generateCode();
                ++$attempt;
            } while ($this->getResource()->exists($code));

            $promoCode->setId(null)
                ->setCampaignId($this->getCampaignId())
                ->setCreatedAt($nowTimestamp)
                ->setPromoCode($code)
                ->save();

            $this->generatedCount += 1;
            $this->generatedCodes[] = $code;
        }

        return $this;
    }

    /**
     * Increase the length of Code if probability is low
     *
     * @return void
     */
    protected function increaseLength()
    {
        $maxProbability = $this->getMaxProbability() ? $this->getMaxProbability() : self::MAX_PROBABILITY_OF_GUESSING;
        $chars = count($this->codeHelper->getCharset($this->getFormat()));
        $size = $this->getQty();
        $length = (int)$this->getLength();
        $maxCodes = pow($chars, $length);
        $probability = $size / $maxCodes;

        if ($probability > $maxProbability) {
            do {
                $length++;
                $maxCodes = pow($chars, $length);
                $probability = $size / $maxCodes;
            } while ($probability > $maxProbability);
            $this->setLength($length);
        }
    }

    /**
     * Validate data input
     *
     * @param array $data
     * @return bool
     */
    public function validateData($data)
    {
        return !empty($data)
        && !empty($data['qty'])
        && !empty($data['campaign_id'])
        && !empty($data['length'])
        && !empty($data['format'])
        && (int)$data['qty'] > 0
        && (int)$data['campaign_id'] > 0
        && (int)$data['length'] > 0;
    }

    /**
     * Return the generated promo codes
     *
     * @return array
     */
    public function getGeneratedCodes()
    {
        return $this->generatedCodes;
    }

    /**
     * Retrieve count of generated promos
     *
     * @return int
     */
    public function getGeneratedCount()
    {
        return $this->generatedCount;
    }
}
