<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_ResponseController extends Mage_Core_Controller_Front_Action {
    
	public function indexAction()
    {
        $this->getResponse()
            ->clearAllHeaders()
            ->setHeader('Content-type', 'text/html; charset=utf8')
            ->setBody($this->getLayout()
            ->createBlock('Interkassa/response')
            ->toHtml());
    }
}