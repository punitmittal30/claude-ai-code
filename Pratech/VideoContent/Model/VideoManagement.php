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

namespace Pratech\VideoContent\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\VideoContent\Api\VideoManagementInterface;
use Pratech\VideoContent\Helper\Video as VideoHelper;

/**
 * Video class to expose videos endpoint
 */
class VideoManagement implements VideoManagementInterface
{
    /**
     * Constant for VIDEO API RESOURCE
     */
    public const VIDEO_API_RESOURCE = 'videos';

    /**
     * Category Constructor
     *
     * @param Response $response
     * @param VideoHelper $videoHelper
     */
    public function __construct(
        private Response    $response,
        private VideoHelper $videoHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getVideos(string $platform, int $pincode): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::VIDEO_API_RESOURCE,
            $this->videoHelper->getVideos($platform, $pincode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getVideosCarousel(string $page, string $platform, int $pincode, string $identifier = ''): array
    {
        return $this->response->getResponse(
            200,
            'success',
            self::VIDEO_API_RESOURCE,
            $this->videoHelper->getVideosCarousel($page, $platform, $pincode, $identifier)
        );
    }
}
