<?php

namespace Pratech\Promotion\Api;

/**
 * Promo Code CRUD interface
 *
 * @api
 * @since 100.0.2
 */
interface PromoCodeRepositoryInterface
{
    /**
     * Save a promo code.
     *
     * @param \Pratech\Promotion\Api\Data\PromoCodeInterface $promoCode
     * @return \Pratech\Promotion\Api\Data\PromoCodeInterface
     * @throws \Magento\Framework\Exception\InputException If there is a problem with the input
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Pratech\Promotion\Api\Data\PromoCodeInterface $promoCode);

    /**
     * Get promo by promo code id.
     *
     * @param int $codeId
     * @return \Pratech\Promotion\Api\Data\PromoCodeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If $codeId is not found
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($codeId);

    /**
     * Retrieve a promo using the specified search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Pratech\Promotion\Api\Data\PromoCodeSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete promo by promo code id.
     *
     * @param int $codeId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($codeId);
}
