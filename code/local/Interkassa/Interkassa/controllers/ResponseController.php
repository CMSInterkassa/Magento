<?php

/**
 * Модуль разработан в компании GateOn предназначен для CMS Magento 1.9
 * Сайт разработчикa: www.gateon.net
 * E-mail: www@smartbyte.pro
 * Версия: 1.4
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
