<?php

/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 1.9.2.4
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.5
 * @update 25.10.2016
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


