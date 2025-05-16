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

namespace Pratech\Promotion\Controller\Adminhtml\PromoCode;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Pratech\Promotion\Block\Adminhtml\PromoCode\Edit\Tab\Promo\Grid;
use Pratech\Promotion\Controller\Adminhtml\PromoCode;

/**
 * Export promos to xml file
 */
class ExportCouponsXml extends PromoCode implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Export promo codes as excel xml file
     *
     * @return ResponseInterface|null
     * @throws \Exception
     */
    public function execute()
    {
        $this->_initRule();
        $fileName = 'promo_codes.xml';
        /** @var Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $content = $resultLayout->getLayout()->createBlock(Grid::class)->getExcelFile($fileName);
        return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}
