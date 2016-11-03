<?php

/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 1.9.2.4
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.5
 * @update 25.10.2016
 */



class Interkassa_Interkassa_ResponseController extends Mage_Core_Controller_Front_Action {
    
	public function indexAction() {

        $this->getResponse()
            ->setHeader('Content-type', 'text/html; charset=utf8')
            ->setBody($this->getLayout()
            ->createBlock('Interkassa/response')
            ->toHtml());
    }
}

?>
