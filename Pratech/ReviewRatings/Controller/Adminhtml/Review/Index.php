<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\ReviewRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var array
     */
    protected array $csvHeader = [
        "review_id",
        "nickname",
        "title",
        "detail",
        "rating_value",
        "sku",
        "status"
    ];

    /**
     * @var array|array[]
     */
    protected array $csvContent = [
        [
            "",
            "John",
            "Nice Product",
            "This is nice product",
            "4",
            "HSAI33Z5",
            "",
        ],
        [
            "",
            "mohan",
            "Nice Product",
            "This is nice product",
            "3",
            "HSAI63Z8",
            ""
        ]
    ];

    /**
     * Constructor
     *
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     * @param FileFactory $fileFactory
     */
    public function __construct(
        protected Context $context,
        protected PageFactory $resultPageFactory,
        protected FileFactory $fileFactory,
    ) {
        parent::__construct($context);
        $this->downloader= $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (isset($this->getRequest()->getParams()['download_sample'])) {
            $content = $this->generateCsvContent();

            return $this->downloader->create(
                'reviews.csv',
                $content,
                DirectoryList::VAR_DIR
            );
        }

        $this->messageManager->addNotice(
            'For status column use {1 = Approved, 2 = Pending, 3 = Not Approved}'
        );

        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('pratech_review_review_index');
        $resultPage->setActiveMenu('Pratech_ReviewRatings::reviews_import');
        $resultPage->getConfig()->getTitle()->prepend(__("Import Reviews"));

        return  $resultPage;
    }

    /**
     * Generate CSV Content
     *
     * @return string
     */
    protected function generateCsvContent()
    {
        $csvContent = implode(",", $this->csvHeader) . "\n";

        foreach ($this->csvContent as $review) {
            $csvContent .= implode(",", $review) . "\n";
        }
        return $csvContent;
    }
}
