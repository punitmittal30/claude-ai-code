<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Controller\Adminhtml\Author;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\CmsBlock\Model\AuthorFactory;
use Psr\Log\LoggerInterface;

/**
 * Author Save Controller
 */
class Save extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Save constructor
     *
     * @param Context         $context
     * @param RedirectFactory $redirectFactory
     * @param AuthorFactory   $authorFactory
     * @param Json            $json
     */
    public function __construct(
        Action\Context        $context,
        RedirectFactory       $redirectFactory,
        private AuthorFactory $authorFactory,
        private Json          $json
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            try {
                $id = $data['author_id'];
                $author = $this->authorFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['author_id']);
                }

                $author->setData($data);
                $author->save();

                $this->_eventManager->dispatch(
                    'author_controller_save_after',
                    ['author' => $author]
                );

                $this->messageManager->addSuccessMessage(__('Author data successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirect->setPath(
                        '*/*/edit',
                        ['id' => $author->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_CmsBlock::authors');
    }
}
