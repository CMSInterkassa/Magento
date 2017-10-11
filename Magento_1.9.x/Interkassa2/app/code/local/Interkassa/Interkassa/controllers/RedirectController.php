<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_RedirectController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('core/template');
        $block->setTemplate('interkassa/interkassa.phtml');

        $this->getLayout()->getBlock('content')->append($block)->setTemplate('page/1column.phtml');

        $this->renderLayout();
    }
}
