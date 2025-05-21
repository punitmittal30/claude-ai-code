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

declare(strict_types=1);

namespace Pratech\Promotion\Model\PromoCode;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\NotifierInterface;
use Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterface;
use Pratech\Promotion\Api\PromoCodeManagementInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumer for export promo codes generation.
 */
class Consumer
{
    /**
     * Consumer constructor.
     * @param LoggerInterface $logger
     * @param PromoCodeManagementInterface $promoCodeManagement
     * @param NotifierInterface $notifier
     */
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private PromoCodeManagementInterface $promoCodeManagement,
        private NotifierInterface $notifier
    ) {
    }

    /**
     * Consumer logic.
     *
     * @param PromoCodeGenerationSpecInterface $exportInfo
     * @return void
     */
    public function process(PromoCodeGenerationSpecInterface $exportInfo)
    {
        try {
            $this->promoCodeManagement->generate($exportInfo);

            $this->notifier->addMajor(
                __('Your promo codes are ready'),
                __('You can check your promo codes at campaign page')
            );
        } catch (LocalizedException $exception) {
            $this->notifier->addCritical(
                __('Error during promo codes generator process occurred'),
                __('Error during promo codes generator process occurred. Please check logs for detail')
            );
            $this->logger->critical(
                'Something went wrong while promo codes generator process. ' . $exception->getMessage()
            );
        }
    }
}
