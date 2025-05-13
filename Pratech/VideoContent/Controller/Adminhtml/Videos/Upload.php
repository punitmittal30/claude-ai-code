<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Controller\Adminhtml\Videos;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList as FilesystemDirectory;

class Upload extends Action
{
    protected $mediaDirectory;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param File $file
     * @param JsonFactory $jsonFactory
     * @param Filesystem $filesystem
     * @throws FileSystemException
     */
    public function __construct(
        Context                   $context,
        protected UploaderFactory $uploaderFactory,
        protected File            $file,
        protected JsonFactory     $jsonFactory,
        Filesystem                $filesystem
    )
    {
        parent::__construct($context);
        $this->mediaDirectory = $filesystem->getDirectoryWrite(FilesystemDirectory::MEDIA);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'url']);
            $uploader->setAllowedExtensions(['mp4', 'avi', 'mov']);

            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $uploadDir = 'video/uploads';
            $result = $uploader->save($this->mediaDirectory->getAbsolutePath($uploadDir));

            if ($result) {
                $fullFilePath = BP . '/pub/media/' . $uploadDir . '/' . $result['file'];
                $size = file_exists($fullFilePath) ? filesize($fullFilePath) : 0;

                return $this->jsonFactory->create()->setData(
                    [
                        'name' => $result['file'],
                        'url' => $uploadDir . $result['file'],
                        'file' => $uploadDir . $result['file'],
                        'size' => $size,
                        'type' => 'video'
                    ]
                );
            }
            throw new Exception(__('File could not be uploaded.'));
        } catch (Exception $e) {
            return $this->jsonFactory->create()->setData(
                [
                    'error' => $e->getMessage(),
                    'errorcode' => 1
                ]
            );
        }
    }
}
