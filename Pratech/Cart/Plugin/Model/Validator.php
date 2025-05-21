<?php

namespace Pratech\Cart\Plugin\Model;

use Magento\Catalog\Helper\Data;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Helper\CartFixedDiscount;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\RulesApplier;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\Validator\Pool;

class Validator extends \Magento\SalesRule\Model\Validator
{

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * Validator Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $collectionFactory
     * @param Data $catalogData
     * @param Utility $utility
     * @param RulesApplier $rulesApplier
     * @param PriceCurrencyInterface $priceCurrency
     * @param Pool $validators
     * @param ManagerInterface $messageManager
     * @param RuleRepositoryInterface $ruleRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param CartFixedDiscount|null $cartFixedDiscount
     */
    public function __construct(
        Context                 $context,
        Registry                $registry,
        CollectionFactory       $collectionFactory,
        Data                    $catalogData,
        Utility                 $utility,
        RulesApplier            $rulesApplier,
        PriceCurrencyInterface  $priceCurrency,
        Pool                    $validators,
        ManagerInterface        $messageManager,
        RuleRepositoryInterface $ruleRepository,
        AbstractResource        $resource = null,
        AbstractDb              $resourceCollection = null,
        array $data = [],
        ?CartFixedDiscount      $cartFixedDiscount = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $collectionFactory,
            $catalogData,
            $utility,
            $rulesApplier,
            $priceCurrency,
            $validators,
            $messageManager,
            $resource,
            $resourceCollection,
            $data,
            $cartFixedDiscount
        );
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * Convert address discount description array to string
     *
     * @param Address $address
     * @param string $separator
     * @return $this
     */
    public function prepareDescription($address, $separator = ', ')
    {
        $couponCode = $address->getQuote()->getCouponCode();
        $address->setDiscountDescription($couponCode);
        return $this;
    }
}
