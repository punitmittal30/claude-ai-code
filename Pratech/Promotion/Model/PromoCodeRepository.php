<?php

namespace Pratech\Promotion\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Pratech\Promotion\Api\Data\PromoCodeSearchResultInterfaceFactory;
use Pratech\Promotion\Model\ResourceModel\PromoCode\Collection;
use Pratech\Promotion\Model\ResourceModel\PromoCode\CollectionFactory;
use Pratech\Promotion\Model\Spi\PromoCodeResourceInterface;

class PromoCodeRepository implements \Pratech\Promotion\Api\PromoCodeRepositoryInterface
{
    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * PromoCodeRepository constructor.
     *
     * @param PromoCodeFactory $promoCodeFactory
     * @param CampaignFactory $campaignFactory
     * @param PromoCodeSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionFactory $collectionFactory
     * @param PromoCodeResourceInterface $resourceModel
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        private \Pratech\Promotion\Model\PromoCodeFactory $promoCodeFactory,
        private \Pratech\Promotion\Model\CampaignFactory $campaignFactory,
        private \Pratech\Promotion\Api\Data\PromoCodeSearchResultInterfaceFactory $searchResultFactory,
        private \Pratech\Promotion\Model\ResourceModel\PromoCode\CollectionFactory $collectionFactory,
        private \Pratech\Promotion\Model\Spi\PromoCodeResourceInterface $resourceModel,
        private \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Save promo.
     *
     * @param \Pratech\Promotion\Api\Data\PromoCodeInterface $promoCode
     * @return \Pratech\Promotion\Api\Data\PromoCodeInterface
     * @throws \Magento\Framework\Exception\InputException If there is a problem with the input
     * @throws \Magento\Framework\Exception\NoSuchEntityException If a promo ID is sent but the promo does not exist
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Pratech\Promotion\Api\Data\PromoCodeInterface $promoCode)
    {
        $codeId = $promoCode->getCodeId();
        if ($codeId) {
            $existingPromoCode = $this->getById($codeId);
            $mergedData = array_merge($existingPromoCode->getData(), $promoCode->getData());
            $promoCode->setData($mergedData);
        }

        //blend in specific fields from the campaign
        try {
            $campaign = $this->campaignFactory->create()->load($promoCode->getCampaignId());
            if (!$campaign->getCampaignId()) {
                throw \Magento\Framework\Exception\NoSuchEntityException::singleField(
                    'campaign_id',
                    $promoCode->getCampaignId()
                );
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error occurred when saving promo code: %1', $e->getMessage())
            );
        }

        $this->resourceModel->save($promoCode);
        return $promoCode;
    }

    /**
     * Get promo by promo id.
     *
     * @param int $codeId
     * @return \Pratech\Promotion\Api\Data\PromoCodeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If $promoId is not found
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($codeId)
    {
        $promoCode = $this->promoCodeFactory->create()->load($codeId);

        if (!$promoCode->getCodeId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }
        return $promoCode;
    }

    /**
     * Retrieve data.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\Promotion\Api\Data\PromoCodeSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Pratech\Promotion\Model\ResourceModel\PromoCode\Collection $collection */
        $collection = $this->collectionFactory->create();
        $promoCodeInterfaceName = \Pratech\Promotion\Api\Data\PromoCodeInterface::class;
        $this->extensionAttributesJoinProcessor->process($collection, $promoCodeInterfaceName);

        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete promo code by code id.
     *
     * @param int $codeId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($codeId)
    {
        /** @var \Pratech\Promotion\Model\PromoCode $promoCode */
        $promoCode = $this->promoCodeFactory->create()
            ->load($codeId);

        if (!$promoCode->getCodeId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        $this->resourceModel->delete($promoCode);
        return true;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(
        FilterGroup $filterGroup,
        Collection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
            );
        }
        return $this->collectionProcessor;
    }
}
