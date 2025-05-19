<?php

namespace Pratech\Promotion\Controller\Adminhtml\PromoCode;

class CodesGrid extends \Pratech\Promotion\Controller\Adminhtml\PromoCode
{
    /**
     * Promo codes grid
     *
     * @return void
     */
    public function execute()
    {
        $this->_initRule();
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Campaign'));
        $this->_view->renderLayout();
    }
}
