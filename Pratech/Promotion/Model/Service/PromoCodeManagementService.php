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

namespace Pratech\Promotion\Model\Service;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Promotion\Api\PromoCodeRepositoryInterface;
use Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterface;
use Pratech\Promotion\Api\Data\PromoCodeMassDeleteResultInterface;
use Pratech\Promotion\Api\Data\PromoCodeMassDeleteResultInterfaceFactory;
use Pratech\Promotion\Api\PromoCodeManagementInterface;
use Pratech\Promotion\Model\CampaignFactory;
use Pratech\Promotion\Model\PromoCode\Massgenerator;

class PromoCodeManagementService implements PromoCodeManagementInterface
{
    /**
     * @var PromoCodeMassDeleteResultInterfaceFactory
     */
    protected $promoCodeMassDeleteResultInterfaceFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var PromoCodeRepositoryInterface
     */
    private $repository;

    /**
     * @param CampaignFactory $campaignFactory
     * @param Massgenerator $promoCodeGenerator
     * @param PromoCodeMassDeleteResultInterfaceFactory $promoCodeMassDeleteResultInterfaceFactory
     * @param SearchCriteriaBuilder|null $criteriaBuilder
     * @param PromoCodeRepositoryInterface|null $repository
     */
    public function __construct(
        private CampaignFactory                   $campaignFactory,
        private Massgenerator                     $promoCodeGenerator,
        PromoCodeMassDeleteResultInterfaceFactory $promoCodeMassDeleteResultInterfaceFactory,
        ?SearchCriteriaBuilder                    $criteriaBuilder = null,
        ?PromoCodeRepositoryInterface             $repository = null
    ) {
        $this->promoCodeMassDeleteResultInterfaceFactory = $promoCodeMassDeleteResultInterfaceFactory;
        $this->criteriaBuilder = $criteriaBuilder ?? ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
        $this->repository = $repository ?? ObjectManager::getInstance()->get(PromoCodeRepositoryInterface::class);
    }

    /**
     * Generate promo for a campaign
     *
     * @param PromoCodeGenerationSpecInterface $promoCodeSpec
     * @return string[]
     * @throws InputException
     * @throws LocalizedException
     */
    public function generate(PromoCodeGenerationSpecInterface $promoCodeSpec)
    {
        $data = $this->convertCouponSpec($promoCodeSpec);
        if (!$this->promoCodeGenerator->validateData($data)) {
            throw new InputException();
        }

        try {
            $campaign = $this->campaignFactory->create()->load($promoCodeSpec->getCampaignId());
            if (!$campaign->getCampaignId()) {
                throw NoSuchEntityException::singleField(
                    'campaign_id',
                    $promoCodeSpec->getCampaignId()
                );
            }

            $this->promoCodeGenerator->setData($data);
            $this->promoCodeGenerator->generatePool();
            return $this->promoCodeGenerator->getGeneratedCodes();
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Error occurred when generating promo codes: %1', $e->getMessage())
            );
        }
    }

    /**
     * Convert promo Spec
     *
     * @param PromoCodeGenerationSpecInterface $promoCodeGenerationSpec
     * @return array
     */
    protected function convertCouponSpec(PromoCodeGenerationSpecInterface $promoCodeGenerationSpec)
    {
        $data = [];
        $data['campaign_id'] = $promoCodeGenerationSpec->getCampaignId();
        $data['qty'] = $promoCodeGenerationSpec->getQuantity();
        $data['format'] = $promoCodeGenerationSpec->getFormat();
        $data['length'] = $promoCodeGenerationSpec->getLength();

        //ensure we have a format
        if (empty($data['format'])) {
            $data['format'] = $promoCodeGenerationSpec::CODE_FORMAT_ALPHANUMERIC;
        }

        return $data;
    }

    /**
     * Delete promo codes by code ids.
     *
     * @param int[] $ids
     * @param bool $ignoreInvalidCoupons
     * @return PromoCodeMassDeleteResultInterface
     * @throws LocalizedException
     */
    public function deleteByIds(array $ids, $ignoreInvalidCoupons = true)
    {
        return $this->massDelete('code_id', $ids, $ignoreInvalidCoupons);
    }

    /**
     * Delete promos by filter
     *
     * @param string $fieldName
     * @param string[] $fieldValues
     * @param bool $ignoreInvalid
     * @return PromoCodeMassDeleteResultInterface
     * @throws LocalizedException
     */
    protected function massDelete($fieldName, array $fieldValues, $ignoreInvalid)
    {
        $this->criteriaBuilder->addFilter($fieldName, $fieldValues, 'in');
        $promoCodesCollection = $this->repository->getList($this->criteriaBuilder->create());

        if (!$ignoreInvalid) {
            if ($promoCodesCollection->getTotalCount() != count($fieldValues)) {
                throw new LocalizedException(__('Some promo codes are invalid.'));
            }
        }

        $results = $this->promoCodeMassDeleteResultInterfaceFactory->create();
        $failedItems = [];
        $fieldValues = array_flip($fieldValues);
        foreach ($promoCodesCollection->getItems() as $promoCode) {
            $promoCodeValue = ($fieldName == 'promo_code') ? $promoCode->getPromoCode() : $promoCode->getCodeId();
            try {
                $this->repository->deleteById($promoCode->getCodeId());
            } catch (Exception $e) {
                $failedItems[] = $promoCodeValue;
            }
            unset($fieldValues[$promoCodeValue]);
        }
        $results->setFailedItems($failedItems);
        $results->setMissingItems(array_flip($fieldValues));
        return $results;
    }

    /**
     * Delete promo by promo codes.
     *
     * @param string[] $codes
     * @param bool $ignoreInvalidCoupons
     * @return PromoCodeMassDeleteResultInterface
     * @throws LocalizedException
     */
    public function deleteByCodes(array $codes, $ignoreInvalidCoupons = true)
    {
        return $this->massDelete('promo_code', $codes, $ignoreInvalidCoupons);
    }
}
