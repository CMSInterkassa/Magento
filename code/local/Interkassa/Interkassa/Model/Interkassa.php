<?php

/**
 * Модуль разработан в компании GateOn предназначен для CMS Magento 1.9
 * Сайт разработчикa: www.gateon.net
 * E-mail: www@smartbyte.pro
 * Версия: 1.4
 */

class Interkassa_Interkassa_Model_Interkassa extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'Interkassa';
    protected $_formBlockType = 'Interkassa/form';

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('Interkassa/redirect', array('_secure' => true));
    }

    public function getQuote()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        return $order;
    }

    public function getFormFields()
    {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount = round($order->getGrandTotal() * 100, 2);
        $fields = array(
            'ik_pm_no' => $order_id,
            'ik_co_id' => $this->getConfigData('merchant'),
            'ik_desc' => '#'.$order_id,
            'ik_am' => $amount,
            'ik_cur' => $this->getConfigData('currency'),
        );


        $params = array(
            'fields' => $fields
        );
        return $params;
    }

}


