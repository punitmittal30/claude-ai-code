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

namespace Pratech\Promotion\Controller\Adminhtml\PromoCode;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Pratech\Promotion\Api\Data\PromoCodeGenerationSpecInterfaceFactory;
use Pratech\Promotion\Controller\Adminhtml\PromoCode;
use Psr\Log\LoggerInterface;

/**
 * Generate promo codes
 */
class Generate extends PromoCode implements HttpPostActionInterface
{
    /**
     * @var PublisherInterface
     */
    private $messagePublisher;

    /**
     * @var PromoCodeGenerationSpecInterfaceFactory
     */
    private $generationSpecFactory;

    /**
     * Generate constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param Date $dateFilter
     * @param PublisherInterface|null $publisher
     * @param PromoCodeGenerationSpecInterfaceFactory|null $generationSpecFactory
     */
    public function __construct(
        Context                                 $context,
        Registry                                $coreRegistry,
        FileFactory                             $fileFactory,
        Date                                    $dateFilter,
        PublisherInterface                      $publisher = null,
        PromoCodeGenerationSpecInterfaceFactory $generationSpecFactory = null
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter);
        $this->messagePublisher = $publisher ?: ObjectManager::getInstance()->get(PublisherInterface::class);
        $this->generationSpecFactory = $generationSpecFactory ?:
            ObjectManager::getInstance()->get(PromoCodeGenerationSpecInterfaceFactory::class);
    }

    /**
     * Generate promos action
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');
            return;
        }
        $result = [];
        $this->_initRule();

        $campaign = $this->_coreRegistry->registry("campaign");

        if (!$campaign->getCampaignId()) {
            $result['error'] = __('Campaign is not defined');
        } else {
            try {
                $data = $this->getRequest()->getParams();

                $data['quantity'] = isset($data['qty']) ? $data['qty'] : null;

                $couponSpec = $this->generationSpecFactory->create(['data' => $data]);

                $this->messagePublisher->publish('campaign.codegenerator', $couponSpec);
                $this->messageManager->addSuccessMessage(
                    __('Message is added to queue, wait to get your promo codes soon')
                );
                $this->_view->getLayout()->initMessages();
                $result['messages'] = $this->_view->getLayout()->getMessagesBlock()->getGroupedHtml();
            } catch (InputException $inputException) {
                $result['error'] = __('Invalid data provided');
            } catch (LocalizedException $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                $result['error'] = __(
                    'Something went wrong while generating promo codes. Please review the log and try again.'
                );
                $this->_objectManager->get(LoggerInterface::class)->critical($e);
            }
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(Data::class)->jsonEncode($result)
        );
    }
}
