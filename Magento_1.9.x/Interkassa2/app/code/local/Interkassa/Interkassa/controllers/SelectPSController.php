<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_SelectPSController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        ob_clean();
        echo $this->getLayout()->createBlock('Interkassa/response')->selectPaySystem();
        exit;
    }
}

