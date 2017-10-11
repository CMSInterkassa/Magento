<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('interkassa/form.phtml');

    }
}