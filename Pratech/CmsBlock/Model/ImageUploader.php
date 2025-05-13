<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;

/**
 * Class to upload image to directory.
 */
class ImageUploader
{
    /**
     * Image temporary path
     */
    public const IMAGE_TMP_PATH = 'cms/tmp/image';

    /**
     * Permanent Image Path
     */
    public const IMAGE_PATH = 'cms/image';

    /**
     * @var string
     */
    protected $baseTmpPath;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $allowedExtensions;

    /**
     * @var string[]
     */
    protected $allowedMimeTypes;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * ImageUploader constructor
     *
     * @param  Database              $coreFileStorageDatabase
     * @param  Filesystem            $filesystem
     * @param  UploaderFactory       $uploaderFactory
     * @param  StoreManagerInterface $storeManager
     * @param  Logger                $apiLogger
     * @param  string                $baseTmpPath
     * @param  string                $basePath
     * @param  string[]              $allowedExtensions
     * @param  string[]              $allowedMimeTypes
     * @throws FileSystemException
     */
    public function __construct(
        protected Database              $coreFileStorageDatabase,
        protected Filesystem            $filesystem,
        protected UploaderFactory       $uploaderFactory,
        protected StoreManagerInterface $storeManager,
        protected Logger                $apiLogger,
        string                          $baseTmpPath = self::IMAGE_TMP_PATH,
        string                          $basePath = self::IMAGE_PATH,
        array                           $allowedExtensions = [],
        array                           $allowedMimeTypes = []
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->baseTmpPath = $baseTmpPath;
        $this->basePath = $basePath;
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes = $allowedMimeTypes;
    }

    /**
     * Checking file for moving and move it
     *
     * @param  string $imageName
     * @return string
     * @throws LocalizedException
     */
    public function moveFileFromTmp(string $imageName): string
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();

        $baseImagePath = $this->getFilePath(
            $basePath,
            Uploader::getNewFileName(
                $this->mediaDirectory->getAbsolutePath(
                    $this->getFilePath($basePath, $imageName)
                )
            )
        );
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $imageName);

        try {
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (Exception $e) {
            $this->apiLogger->critical($e->getMessage() . __METHOD__);
            throw new LocalizedException(
                __('Something went wrong while saving the file(s).')
            );
        }

        return $imageName;
    }

    /**
     * Retrieve base tmp path
     *
     * @return string
     */
    public function getBaseTmpPath(): string
    {
        return $this->baseTmpPath;
    }

    /**
     * Set base tmp path
     *
     * @param  string $baseTmpPath
     * @return void
     */
    public function setBaseTmpPath(string $baseTmpPath): void
    {
        $this->baseTmpPath = $baseTmpPath;
    }

    /**
     * Retrieve base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set base path
     *
     * @param  string $basePath
     * @return void
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Retrieve path
     *
     * @param  string $path
     * @param  string $imageName
     * @return string
     */
    public function getFilePath(string $path, string $imageName): string
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Checking file for save and save it to tmp dir
     *
     * @param  string $fileId
     * @return string[]
     * @throws LocalizedException
     */
    public function saveFileToTmpDir(string $fileId): array
    {
        $baseTmpPath = $this->getBaseTmpPath();

        /**
 * @var \Magento\MediaStorage\Model\File\Uploader $uploader
*/
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions($this->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);
        if (!$uploader->checkMimeType($this->allowedMimeTypes)) {
            throw new LocalizedException(__('File validation failed.'));
        }
        $result = $uploader->save($this->mediaDirectory->getAbsolutePath($baseTmpPath));
        unset($result['path']);

        if (!$result) {
            $this->apiLogger->critical('File can not be saved to the destination folder.');
            throw new LocalizedException(
                __('File can not be saved to the destination folder.')
            );
        }

        /**
         * Workaround for prototype 1.7 methods "isJSON", "evalJSON" on Windows OS
         */
        $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
        $result['url'] = $this->storeManager
            ->getStore()
            ->getBaseUrl(
                UrlInterface::URL_TYPE_MEDIA
            ) . $this->getFilePath($baseTmpPath, $result['file']);
        $result['name'] = $result['file'];

        if (isset($result['file'])) {
            try {
                $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                $this->coreFileStorageDatabase->saveFile($relativePath);
            } catch (Exception $e) {
                $this->apiLogger->critical($e);
                throw new LocalizedException(
                    __('Something went wrong while saving the file(s).')
                );
            }
        }

        return $result;
    }

    /**
     * Retrieve allowed extensions
     *
     * @return array|string
     */
    public function getAllowedExtensions(): array|string
    {
        return $this->allowedExtensions;
    }

    /**
     * Set allowed extensions
     *
     * @param  string[] $allowedExtensions
     * @return void
     */
    public function setAllowedExtensions(array $allowedExtensions): void
    {
        $this->allowedExtensions = $allowedExtensions;
    }
}
