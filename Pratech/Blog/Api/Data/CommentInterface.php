<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Blog\Api\Data;

interface CommentInterface
{
    public const COMMENT_ID = 'comment_id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const BLOG_ID = 'blog_id';
    public const SUMMARY = 'summary';
    public const TITLE = 'title';
    public const CREATED_AT = 'created_at';
    public const STATUS = 'status';

    /**
     * Get comment_id
     *
     * @return string|null
     */
    public function getCommentId();

    /**
     * Set comment_id
     *
     * @param string $commentId
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setCommentId($commentId);

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     *
     * @param string $name
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setName($name);

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email
     *
     * @param string $email
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setEmail($email);

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     *
     * @param string $title
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setTitle($title);

    /**
     * Get summary
     *
     * @return string|null
     */
    public function getSummary();

    /**
     * Set summary
     *
     * @param string $summary
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setSummary($summary);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setStatus($status);

    /**
     * Get blog_id
     *
     * @return string|null
     */
    public function getBlogId();

    /**
     * Set blog_id
     *
     * @param string $blogId
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setBlogId($blogId);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return \Pratech\Blog\Comment\Api\Data\CommentInterface
     */
    public function setCreatedAt($createdAt);
}
