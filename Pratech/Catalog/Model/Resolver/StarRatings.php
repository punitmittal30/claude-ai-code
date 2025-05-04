<?php

namespace Pratech\Catalog\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\Review\Config as ReviewsConfig;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Store\Api\Data\StoreInterface;

class StarRatings implements ResolverInterface
{

    /**
     * @param SummaryFactory $summaryFactory
     * @param ReviewsConfig $reviewsConfig
     */
    public function __construct(
        private SummaryFactory $summaryFactory,
        private ReviewsConfig  $reviewsConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $this->reviewsConfig->isEnabled()) {
            return 0;
        }

        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('Value must contain "model" property.'));
        }

        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        $product = $value['model'];

        try {
            $summary = $this->summaryFactory->create()->setStoreId($store->getId())->load($product->getId());
            return number_format((floatval($summary->getData('rating_summary')) / 20), 1);
        } catch (Exception $e) {
            throw new GraphQlInputException(__('Couldn\'t get the product rating summary.'));
        }
    }
}
