<?php
/**
 * Модуль разработан в компании GateOn предназначен для CMS Magento 1.9
 * Сайт разработчикa: www.gateon.net
 * E-mail: www@smartbyte.pro
 * Версия: 1.4
 */

class Interkassa_Interkassa_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Interkassa/form.phtml');

    }
}